# Fix Native Flash Messages

You may have noticed that our flash messages are not behaving correctly in the native side. That's because of how our native client handles redirects. It looks like it actiaves the cache first, which triggers a new request to the web app, then it visits the redirected URL, which then triggers another visit. Our flash messages are flashed for a single subsequent request, which is the one that comes from the activated cache, so they get lost when the redirect visit starts.

We can fix that by using some predefined URLs in the webapp that will only happen for Turbo Native requests and they only instruct the web app to either recede, resume, or refresh the screens natively instead of following redirects.

Turbo Laravel ships with those predefined URLs for you:

```bash
php artisan route:list | grep turbo
  GET|HEAD        recede_historical_location turbo_recede_historical_location…
  GET|HEAD        refresh_historical_location turbo_refresh_historical_locati…
  GET|HEAD        resume_historical_location turbo_resume_historical_location…
```

If you make a request to those routes, you will see that they don't actually have any contents on them:

```bash
curl localhost/recede_historical_location
Going back...

curl localhost/refresh_historical_location
Refreshing...

curl localhost/resume_historical_location
Staying put...
```

Turbo Laravel ships with a `InteractsWithTurboNativeNavigation` trait that we can use in our controllers to redirect to these routes when the request comes from a Turbo Native client or to a fallback route otherwise.

Let's change our controller to make use of that. We're going to change both the store and update actions to _recede_ the screen stacks instead of a regular redirect:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Chirp;
use Illuminate\Http\Request;
use Tonysm\TurboLaravel\Http\Controllers\Concerns\InteractsWithTurboNativeNavigation; // [tl! add]

class ChirpController extends Controller
{
    use InteractsWithTurboNativeNavigation; // [tl! add]
    // [tl! collapse:start]
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('chirps.index', [
            'chirps' => Chirp::with('user:id,name')->latest()->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('chirps.create');
    }
    // [tl! collapse:end]
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:255'],
        ]);

        $chirp = $request->user()->chirps()->create($validated);

        if ($request->wantsTurboStream()) {
        if ($request->wantsTurboStream() && ! $request->wasFromTurboNative()) { // [tl! remove:-1,1 add]
            return turbo_stream([
                turbo_stream($chirp, 'prepend'),
                turbo_stream()->update('create_chirp', view('chirps._form')),
                turbo_stream()->append('notifications', view('layouts.notification', [
                    'message' => __('Chirp created.'),
                ])),
            ]);
        }

        return $this->recedeOrRedirectTo(route('chirps.index')) // [tl! add]
            ->with('status', __('Chirp created.')); // [tl! add]
    }
    // [tl! collapse:start]
    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Chirp  $chirp
     * @return \Illuminate\Http\Response
     */
    public function show(Chirp $chirp)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Chirp  $chirp
     * @return \Illuminate\Http\Response
     */
    public function edit(Chirp $chirp)
    {
        $this->authorize('update', $chirp);

        return view('chirps.edit', [
            'chirp' => $chirp,
        ]);
    }
    // [tl! collapse:end]
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Chirp  $chirp
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Chirp $chirp)
    {
        $this->authorize('update', $chirp);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:255'],
        ]);

        $chirp->update($validated);

        if ($request->wantsTurboStream()) {
        if ($request->wantsTurboStream() && ! $request->wasFromTurboNative()) { // [tl! remove:-1,1 add]
            return turbo_stream([
                turbo_stream($chirp),
                turbo_stream()->append('notifications', view('layouts.notification', [
                    'message' => __('Chirp updated.'),
                ])),
            ]);
        }

        return $this->recedeOrRedirectTo(route('chirps.index')) // [tl! add]
            ->with('status', __('Chirp updated.')); // [tl! add]
    }
    // [tl! collapse:start]
    /**
     * Remove the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Chirp         $chirp
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Chirp $chirp)
    {
        $this->authorize('delete', $chirp);

        $chirp->delete();

        if ($request->wantsTurboStream()) {
            return turbo_stream([
                turbo_stream($chirp),
                turbo_stream()->append('notifications', view('layouts.notification', [
                    'message' => __('Chirp deleted.'),
                ])),
            ]);
        }

        return redirect()
            ->route('chirps.index')
            ->with('status', __('Chirp deleted.'));
    }
    // [tl! collapse:end]
}
```

The `recedeOrRedirectTo` method is the one that does the job of checking if the request comes from a Turbo Native client or a regular web client and recides to redirect to the recede route instead of default redirect route. We also need to ensure that for when we're creating or updating chirps, we only return Turbo Streams if the request didn't come from a Turbo Native client.

Next, we need to configure our specific routes in the `Constants.kt` file:

```kotlin
package com.example.turbochirpernative.util

const val BASE_URL = "http://10.0.2.2"
const val CHIRPS_HOME_URL = "$BASE_URL/chirps"
const val CHIRPS_CREATE_URL = "$BASE_URL/chirps/create"
// [tl! add:start]
// Native Redirect Routes
const val REFRESH_HISTORICAL_URL = "$BASE_URL/refresh_historical_location"
const val RECEDE_HISTORICAL_URL = "$BASE_URL/recede_historical_location"
const val RESUME_HISTORICAL_URL = "$BASE_URL/resume_historical_location"
// [tl! add:end]
const val API_BASE_URL = "$BASE_URL/api"
const val API_CSRF_COOKIES_URL = "$BASE_URL/sanctum/csrf-cookie"
const val API_LOGIN_URL = "$API_BASE_URL/login"
```

Now, we need to update our main `WebFragment` (which is inherited by our sub fragments like the modal one) to catch redirects to these specific routes and stop the navigation and instead apply their native behavior in the navigation stack:

```kotlin
package com.example.turbochirpernative.features.web

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import com.example.turbochirpernative.R
// [tl! add:start]
import com.example.turbochirpernative.util.RECEDE_HISTORICAL_URL
import com.example.turbochirpernative.util.REFRESH_HISTORICAL_URL
import com.example.turbochirpernative.util.RESUME_HISTORICAL_URL
// [tl! add:end]
import dev.hotwire.turbo.fragments.TurboWebFragment
import dev.hotwire.turbo.nav.TurboNavGraphDestination

@TurboNavGraphDestination(uri = "turbo://fragment/web")
open class WebFragment: TurboWebFragment() {

    override fun onCreateView(inflater: LayoutInflater, container: ViewGroup?, savedInstanceState: Bundle?): View? {
        return inflater.inflate(R.layout.fragment_web, container, false)
    }
    // [tl! add:start]
    override fun shouldNavigateTo(newLocation: String): Boolean {
        return when (newLocation) {
            RECEDE_HISTORICAL_URL -> {
                navigateBack()
                false
            }
            REFRESH_HISTORICAL_URL -> {
                refresh()
                false
            }
            RESUME_HISTORICAL_URL -> {
                navigateUp()
                false
            }
            else -> super.shouldNavigateTo(newLocation)
        }
    }
    // [tl! add:end]
}
```

Now, if we try creating or updating a _Chirp_, we should see the web flash messages appearing!

![Web Flash Messages on Native](/images/native/flash-messages-web-on-native.png)

## Flash as Toast Messages

It's cool that we're showing the web flash messages, but it would be even better if we could instead convert those messages to appear as real native Toasts, don't you think?! So, let's implement that.

First, we'll need to hide the flash messages wrapper, so they don't appear in a Turbo Native context. We can do that using the `turbo-native:` Tailwind variant we already have. Open the `resources/views/layouts/notifications.blade.php` file and update its contents:

```blade
<div id="notifications" class="fixed top-10 left-0 w-full text-center flex flex-col items-center space-y-2 justify-center z-10 opacity-80">
<div id="notifications" class="fixed top-10 left-0 w-full text-center flex flex-col items-center space-y-2 justify-center z-10 opacity-80 turbo-native:hidden"> <!-- [tl! remove:-1,1 add] -->
    @if ($message = session()->get('status', null))
        @include('layouts.notification', ['message' => $message])
    @endif
</div>
```

With that, the flash messages shouldn't appear in Turbo Native clients, but they should still appear in the web ones.

Next, let's create a bridge Stimulus controller that will send a message to the Native client to show convert those web flash messages into native ones.

```bash
php artisan stimulus:make bridge/toast
```

We'll update the notification view (the singular one) and make it use the controller we just created. It's important to note that the bridge controller needs to be listed before all other controllers:

```blade
<div data-turbo-cache="false" class="py-1 px-4 leading-7 text-center text-white rounded-full bg-gray-900 transition-all animate-appear-then-fade" data-controller="notification" data-action="animationend->notification#remove">
<div data-turbo-cache="false" class="py-1 px-4 leading-7 text-center text-white rounded-full bg-gray-900 transition-all animate-appear-then-fade" data-controller="bridge--toast notification" data-action="animationend->notification#remove"> <!-- [tl! remove:-1,1 add] -->
    {{ $message }}
</div>
```

Now, let's implement our Stimulus Toast controller:

```js
import { Controller } from "@hotwired/stimulus"
import { isMobileApp } from "../../helpers/platform"

// Connects to data-controller="bridge--toast"
export default class extends Controller {
    connect() {
        if (! this.enabled) return

        window.NativeBridge.showToast(this.element.textContent.trim())
        this.element.remove();
    }

    get enabled() {
        return isMobileApp
    }
}
```

It will simply read the text content of the flash message and pass it up to the native client to as a toast, then it removes the flash element from the page.

Now, we need to implement the `showToast` handling in our `MainSessionNavHostFragment`:

```kotlin
package com.example.turbochirpernative.main
// [tl! collapse:start]
import android.content.DialogInterface
import android.webkit.JavascriptInterface
import android.webkit.WebView
import android.widget.Button
// [tl! collapse:end]
import android.widget.LinearLayout
import android.widget.Toast // [tl! add]
import androidx.appcompat.app.AlertDialog
// [tl! collapse:start]
import androidx.appcompat.app.AppCompatActivity
import androidx.fragment.app.Fragment
import com.example.turbochirpernative.BuildConfig
import com.example.turbochirpernative.R
import com.example.turbochirpernative.features.auth.LoginFragment
import com.example.turbochirpernative.features.web.ChirpsHomeFragment
import com.example.turbochirpernative.features.web.WebFragment
import com.example.turbochirpernative.features.web.WebModalFragment
import com.example.turbochirpernative.util.CHIRPS_HOME_URL
import com.google.android.material.bottomsheet.BottomSheetDialog
import com.google.gson.Gson
import dev.hotwire.turbo.config.TurboPathConfiguration
import dev.hotwire.turbo.session.TurboSessionNavHostFragment
import kotlin.reflect.KClass
// [tl! collapse:end]
class MainSessionNavHostFragment : TurboSessionNavHostFragment(), PopupMenuDelegator {
    // [tl! collapse:start]
    override val sessionName = "main"

    override val startLocation = CHIRPS_HOME_URL

    override val registeredActivities: List<KClass<out AppCompatActivity>>
        get() = listOf()

    override val registeredFragments: List<KClass<out Fragment>>
        get() = listOf(
            WebFragment::class,
            WebModalFragment::class,
            LoginFragment::class,
            ChirpsHomeFragment::class,
        )

    override val pathConfigurationLocation: TurboPathConfiguration.Location
        get() = TurboPathConfiguration.Location(
            assetFilePath = "json/configuration.json",
        )

    override fun onSessionCreated() {
        super.onSessionCreated()
        session.webView.settings.userAgentString = customUserAgent(session.webView)

        if (BuildConfig.DEBUG) {
            session.setDebugLoggingEnabled(true)
            WebView.setWebContentsDebuggingEnabled(true)
        }

        session.webView.addJavascriptInterface(
            JsBridge(this),
            "NativeBridge",
        )
    }

    private fun customUserAgent(webView: WebView): String {
        return "Turbo Native Android ${webView.settings.userAgentString}"
    }

    override fun showPopupMenu(options: PopupMenu) {
        activity?.runOnUiThread {
            val context = requireContext()

            val dialog = BottomSheetDialog(context)

            val view = layoutInflater.inflate(R.layout.popup_menu, null)!!

            val menuWrapper = view.findViewById<LinearLayout>(R.id.popupMenuWrapper)!!

            menuWrapper.removeAllViews()

            options.items.forEach { item ->
                val button = Button(context)

                button.text = item.text
                button.setBackgroundColor(resources.getColor(R.color.white, null))

                button.setOnClickListener {
                    session.webView.evaluateJavascript(
                        "window.dispatchEvent(new CustomEvent('popup-menu:selected', { detail: { index: " + item.index + ", text: '" + item.text + "' } }))",
                        null
                    )

                    dialog.dismiss()
                }

                menuWrapper.addView(button)
            }

            dialog.setOnCancelListener {
                session.webView.evaluateJavascript("window.dispatchEvent(new CustomEvent('popup-menu:canceled'))", null)
            }

            dialog.setCancelable(true)

            dialog.setContentView(view)

            dialog.show()
        }
    }
    // [tl! collapse:end]
    // [tl! add:start]
    override fun showToast(msg: String) {
        activity?.runOnUiThread {
            Toast
                .makeText(requireContext(), msg, Toast.LENGTH_SHORT)
                .show()
        }
    }
    // [tl! add:end]
    // [tl! collapse:start]
    override fun showConfirmationModal(msg: String) {
        activity?.runOnUiThread {
            val builder = AlertDialog.Builder(requireContext())
            builder.setTitle("Confirmation")
            builder.setMessage(msg)
            builder.setPositiveButton("Yes", DialogInterface.OnClickListener { dialog, id ->
                session.webView.evaluateJavascript(
                    "window.dispatchEvent(new CustomEvent('confirmation:handle', { detail: 'confirmed' }))",
                    null
                )
                dialog.dismiss()
            })

            builder.setNegativeButton("No", DialogInterface.OnClickListener { dialog, id ->
                session.webView.evaluateJavascript(
                    "window.dispatchEvent(new CustomEvent('confirmation:handle', { detail: 'canceled' }))",
                    null
                )
                dialog.dismiss()
            })

            val alert = builder.create()

            alert.show()
        }
    }
    // [tl! collapse:end]
}
// [tl! collapse:start]
data class MenuItem(
    val text: String,
    val index: Int,
)

data class PopupMenu(
    val items: List<MenuItem>,
)
// [tl! collapse:end]
interface PopupMenuDelegator {
    fun showPopupMenu(options: PopupMenu);
    fun showToast(msg: String); // [tl! add]
    fun showConfirmationModal(msg: String);
}

class JsBridge(private var delegator: PopupMenuDelegator) {
    // [tl! collapse:start]
    @JavascriptInterface
    override fun toString(): String {
        return "NativeBridge"
    }

    @JavascriptInterface
    fun showPopup(json: String) {
        val gson = Gson()

        val options = gson.fromJson(json, PopupMenu::class.java)

        delegator.showPopupMenu(options)
    }
    // [tl! collapse:end]
    // [tl! add:start]
    @JavascriptInterface
    fun showToast(msg: String) {
        delegator.showToast(msg)
    }
    // [tl! add:end]
    @JavascriptInterface
    fun showConfirmationModal(msg: String) {
        delegator.showConfirmationModal(msg)
    }
}
```

Now, we should have the native Toast messages!

![Flash Messages as Toast](/images/native/flash-messages-toast.png)

What is cool about this is that we have full control over the text message shown in the notification from our web app! Let's change the message to use an emoji, for instance:

![Flash Messages Deleted With Emoji on Native](/images/native/flash-messages-changed-emoji-native.png)
![Flash Messages Deleted With Emoji on Web](/images/native/flash-messages-changed-emoji-web.png)

That's it!

[Continue to the conclusion...](/conclusion.md)
