# Editing in a Modal

[TOC]

## Introduction

Right now our editing flow is not very mobile-friendly. Instead of showing the edit form inline in the list, we could show it as a native modal screen instead. But before we introduce the new native screen, let's first ensure our web dropdown appears as a real native bottom sheet list of options. That will serve as an example of how we can bridge the web and mobile native Worlds with a little bit of JavaScript. This approach was based on how the Hey app works (at least on the pieces I could spot from inspecting the page source).

## Dropdowns in a BottomSheet modal

Let's first create our BottomSheet. This one won't be driven by a navigation, though. We're going implement a web->native bridge that we can trigger when the app is running inside a Turbo Native client. For now, let's create a new XML view. Head to the sidebar, under "res/layout", right-click on it an choose "New -> Layout Resource File". Call it "popup_menu.xml" and add this content:

```xml
<?xml version="1.0" encoding="utf-8"?>
<androidx.cardview.widget.CardView
    xmlns:android="http://schemas.android.com/apk/res/android"
    android:layout_width="match_parent"
    android:layout_height="wrap_content">

    <LinearLayout
        android:id="@+id/popupMenuWrapper"
        android:layout_width="match_parent"
        android:layout_height="wrap_content"
        android:layout_margin="2dp"
        android:orientation="vertical">
    </LinearLayout>

</androidx.cardview.widget.CardView>
```

This will be programatically added, so what's really important here is the `android:id` property. Since we're going to trigger this native feature from the web, the Turbo Native for Android [documentation says](https://github.com/hotwired/turbo-android/blob/main/docs/ADVANCED-OPTIONS.md#native---javascript-integration) that the best location is in the `TurboSessionNavHostFragment::onSessionCreated` function. Let's then tweak our `MainSessionNavHostFragment` with the following change:

```kotlin
package com.example.turbochirpernative.main

import android.webkit.JavascriptInterface // [tl! add]
import android.webkit.WebView
import android.widget.Button // [tl! add:start]
import android.widget.LinearLayout
import android.widget.Toast // [tl! add:end]
import androidx.appcompat.app.AppCompatActivity
import androidx.compose.ui.res.integerResource
import androidx.fragment.app.Fragment
import com.example.turbochirpernative.BuildConfig
import com.example.turbochirpernative.R // [tl! add]
import com.example.turbochirpernative.features.auth.LoginFragment
import com.example.turbochirpernative.features.web.ChirpsHomeFragment
import com.example.turbochirpernative.features.web.WebFragment
import com.example.turbochirpernative.util.CHIRPS_HOME_URL
import com.google.android.material.bottomsheet.BottomSheetDialog // [tl! add]
import com.google.gson.Gson // [tl! add]
import dev.hotwire.turbo.config.TurboPathConfiguration
import dev.hotwire.turbo.session.TurboSessionNavHostFragment
import kotlin.reflect.KClass

class MainSessionNavHostFragment : TurboSessionNavHostFragment() {
class MainSessionNavHostFragment : TurboSessionNavHostFragment(), PopupMenuDelegator { // [tl! remove:-1,1 add]
    // [tl! collapse:start]
    override val sessionName = "main"

    override val startLocation = CHIRPS_HOME_URL

    override val registeredActivities: List<KClass<out AppCompatActivity>>
        get() = listOf()

    override val registeredFragments: List<KClass<out Fragment>>
        get() = listOf(
            WebFragment::class,
            LoginFragment::class,
            ChirpsHomeFragment::class,
        )

    override val pathConfigurationLocation: TurboPathConfiguration.Location
        get() = TurboPathConfiguration.Location(
            assetFilePath = "json/configuration.json",
        )
    // [tl! collapse:end]
    override fun onSessionCreated() {
        super.onSessionCreated()
        session.webView.settings.userAgentString = customUserAgent(session.webView)

        if (BuildConfig.DEBUG) {
            session.setDebugLoggingEnabled(true)
            WebView.setWebContentsDebuggingEnabled(true)
        }
        // [tl! add:start]
        session.webView.addJavascriptInterface(
            JsBridge(this),
            "NativeBridge",
        )
        // [tl! add:end]
    }

    private fun customUserAgent(webView: WebView): String {
        return "Turbo Native Android ${webView.settings.userAgentString}"
    }
    // [tl! add:start]
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

    override fun showToast(msg: String) {
        activity?.runOnUiThread {
            Toast
                .makeText(requireContext(), msg, Toast.LENGTH_SHORT)
                .show()
        }
    }
}

data class MenuItem(
    val text: String,
    val index: Int,
)

data class PopupMenu(
    val items: List<MenuItem>,
)

interface PopupMenuDelegator {
    fun showPopupMenu(options: PopupMenu);
    fun showToast(msg: String);
}

class JsBridge(private var delegator: PopupMenuDelegator) {
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

    @JavascriptInterface
    fun showToast(msg: String) {
        delegator.showToast(msg)
    }
    // [tl! add:end]
}
```

Now we have the BottomSheet Menu ready to be triggered by our webapp.

## Telling the Native App to Show The Options

We're gonna create a Stimulus controller that will act when a user triggers the dropdown inside a Turbo Native client. We're also gonna use the custom User Agent to detect we're on that platform. Whenever that is the case, we'll get some metadata from the HTML and pass that to the Native app so it can build the native menu. When the user either picks one of the options or dismisses the menu, we're gonna notify the web app about it.

Let's then add the Stimulus controller. Open a terminal at the root of the webapp and run:

```bash
php artisan stimulus:make bridge/popup_menu_controller
```

That should take care of creating and registering the controller for us. Open that Stimulus controller and place the following content:

```js
import { Controller } from "@hotwired/stimulus"
import { isMobileApp } from "../../helpers/platform"
import { BridgeElement } from "../../helpers/bridge_element"

// Connects to data-controller="bridge--popup-menu"
export default class extends Controller {
    static targets = ['option']

    connect() {
        this.clearCallbacks()
    }

    update(event) {
        if (! this.enabled) return

        event.stopImmediatePropagation()
        event.preventDefault()

        this.notifyBridgeToDisplayMenu(event)
    }

    notifyBridgeToDisplayMenu(event) {
        const items = BridgeElement.makeMenuItems(this.optionTargets)

        this.send(items, item => {
            new BridgeElement(this.optionTargets[item.index]).click()
        })
    }

    send(items, callback) {
        this.registeredCallbacks.push(callback)
        window.NativeBridge.showPopup(JSON.stringify({ items }))
    }

    handle(event) {
        let handler = this.registeredCallbacks.pop()

        if (! handler) return

        handler.call(this, event.detail)
    }

    clearCallbacks() {
        this.registeredCallbacks = []
    }

    get enabled() {
        return isMobileApp
    }
}
```

So, we're detecting if we're on a Turbo Native client using the `isMobileApp` platform check (that same one we're using to add the `turbo-native` CSS class to the HTML document). The `update` method will be triggered by the dropdown trigger (same one that opens it), but we need to register it *before* the normal web trigger because we want to stop it from showing the dropdown and, instead, show the native menu. When the dropdown opens, we'll scan through all the option targets and fetch metadata from it, then register a callback in the controller's instance. When the user picks one of the options, the native client will dispatch a custom event to the window, so all instances of dropdown controllers will receive that event, but only the one with the callback will act on it. Then, it should trigger the default behavior of that option (link or button click).

When the controller scans the option targets, it builds an instance of a `BridgeElement` class, which we don't have yet. Let's add one at `resources/js/helpers/bridge_element.js`:

```js
import { isAndroidApp, isMobileApp } from "./platform"

export class BridgeElement {
    static makeMenuItems(elements){
        return elements.map((element, index) => {
            return new BridgeElement(element).asMenuItem(index)
        }).filter(item => item)
    }

    constructor(element) {
        this.element = element
    }

    asMenuItem(index) {
        if (this.disabled) return

        return {
            text: this.text,
            index: index,
        }
    }

    click() {
        this.ensureFrameOrFormTargetsTop()

        // Remove the target attribute before clicking to avoid an
        // issue in Android WebView that prevents a target="_blank"
        // url from being obtained from a javascript click.

        if (isAndroidApp) {
            this.element.removeAttribute("target")
        }

        this.element.click()
    }

    ensureFrameOrFormTargetsTop() {
        this.ensureFrameTargetsTop()
        this.ensureFormTargetsTop()
    }

    ensureFrameTargetsTop() {
        let frame = this.element.closest('turbo-frame')

        if (! frame) return

        frame.setAttribute('target', '_top')
    }

    ensureFormTargetsTop() {
        let form = this.element.closest('form')

        if (! form) return

        form.setAttribute('data-turbo-frame', '_top')
    }

    get text() {
        return this.element.textContent.trim()
    }

    get disabled() {
        return ! this.enabled
    }

    get enabled() {
        return isMobileApp
    }
}
```

Next, let's update the our `dropdown` blade component to use this Stimulus controller we just created:

```blade
@props(['align' => 'right', 'width' => '48', 'contentClasses' => 'py-1 bg-white'])

@php
switch ($align) {
    case 'left':
        $alignmentClasses = 'origin-top-left left-0';
        break;
    case 'top':
        $alignmentClasses = 'origin-top';
        break;
    case 'right':
    default:
        $alignmentClasses = 'origin-top-right right-0';
        break;
}

switch ($width) {
    case '48':
        $width = 'w-48';
        break;
}
@endphp
<!-- [tl! add:4,1 remove:3,1] -->
<div
    class="relative"
    data-controller="dropdown"
    data-controller="bridge--popup-menu dropdown"
    data-action="
        click@window->dropdown#close
        turbo:load@window->dropdown#close
        popup-menu:canceled@window->bridge--popup-menu#clearCallbacks
        popup-menu:selected@window->bridge--popup-menu#handle
    "
    {{ $attributes }}
>
    <!-- [tl! add:-5,2] -->
    <div data-action="click->dropdown#toggle click->dropdown#stop">
    <div data-action="click->bridge--popup-menu#update click->dropdown#toggle click->dropdown#stop"> <!-- [tl! remove:-1,1 add] -->
        {{ $trigger }}
    </div>

    <div
        data-dropdown-target="content"
        data-transition-enter="transition ease-out duration-200"
        data-transition-enter-start="transform opacity-0 scale-95"
        data-transition-enter-end="transform opacity-100 scale-100"
        data-transition-leave="transition ease-in duration-75"
        data-transition-leave-start="transform opacity-100 scale-100"
        data-transition-leave-end="transform opacity-0 scale-95"
        class="hidden absolute z-50 mt-2 {{ $width }} rounded-md shadow-lg {{ $alignmentClasses }}"
    >
        <div class="rounded-md ring-1 ring-black ring-opacity-5 {{ $contentClasses }}">
            {{ $content }}
        </div>
    </div>
</div>
```

Now, we need to make sure the options are registered in the Stimulus controller where this Blade component is used. Let's update our `_chirp` blade partial:

```blade
<x-turbo-frame :id="$chirp" class="block p-6">
    <div class="flex space-x-2">
        <!-- [tl! collapse:start] -->
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-600 -scale-x-100" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
        </svg>
        <!-- [tl! collapse:end] -->
        <div class="flex-1">
            <div class="flex justify-between items-center">
                <!-- [tl! collapse:start] -->
                <div>
                    <span class="text-gray-800">{{ $chirp->user->name }}</span>
                    <x-time-ago :date="$chirp->created_at" />
                    @unless ($chirp->created_at->eq($chirp->updated_at))
                    <small class="text-sm text-gray-600"> &middot; edited</small>
                    @endunless
                </div>
                <!-- [tl! collapse:end] -->
                @if (Auth::id() === $chirp->user->id)
                <x-dropdown align="right" width="48" data-bridge--popup-menu-msg-value="{{ $chirp->message }}">
                    <!-- [tl! collapse:start] -->
                    <x-slot name="trigger">
                        <button>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                            </svg>
                        </button>
                    </x-slot>
                    <!-- [tl! collapse:end] -->
                    <x-slot name="content">
                        <a href="{{ route('chirps.edit', $chirp) }}" class="block w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:bg-gray-100 transition duration-150 ease-in-out">
                        <a href="{{ route('chirps.edit', $chirp) }}" data-bridge--popup-menu-target="option" class="block w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:bg-gray-100 transition duration-150 ease-in-out"> <!-- [tl! remove:-1,1 add] -->
                            Edit
                        </a>

                        <form action="{{ route('chirps.destroy', $chirp) }}" method="POST">
                            @method('DELETE')
                            <button class="block w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:bg-gray-100 transition duration-150 ease-in-out">
                            <button data-bridge--popup-menu-target="option" class="block w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:bg-gray-100 transition duration-150 ease-in-out"> <!-- [tl! remove:-1,1 add] -->
                                Delete
                            </button>
                        </form>
                    </x-slot>
                </x-dropdown>
                @endif
            </div>
            <p class="mt-4 text-lg text-gray-900">{{ $chirp->message }}</p>
        </div>
    </div>
</x-turbo-frame>
```

This should get our dropdown appearing as a BottomSheet menu!

![Edit Bottom Sheet Menu](/images/native/edit-bottom-sheet-menu.png)

Our Bridge Popup Menu controller also changes the frame target to `_top`, which is handy in our case as that will make a full page visit to the edit page instead of rendering the form inline!

If you try to update a chirp, however, the behavior will be similar to what it was previously on the create chirp flow. Let's fix it by letting the controller redirect to the list index of chirps instead of returning Turbo Streams. Since we're tinkering with the controller, let's also update the destroy flow so it also redirects there:

```php
<?php
// [tl! collapse:start]
namespace App\Http\Controllers;

use App\Models\Chirp;
use Illuminate\Http\Request;
// [tl! collapse:end]
class ChirpController extends Controller
{
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

        if ($request->wantsTurboStream() && ! $request->wasFromTurboNative()) {
            return turbo_stream([
                turbo_stream($chirp, 'prepend'),
                turbo_stream()->update('create_chirp', view('chirps._form')),
                turbo_stream()->append('notifications', view('layouts.notification', [
                    'message' => __('Chirp created.'),
                ])),
            ]);
        }

        return redirect()
            ->route('chirps.index')
            ->with('status', __('Chirp created.'));
    }

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

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Chirp  $chirp
     * @return \Illuminate\Http\Response
     */
    // [tl! collapse:end]
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

        return redirect()
            ->route('chirps.index')
            ->with('status', __('Chirp updated.'));
    }
    // [tl! collapse:start]
    /**
     * Remove the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Chirp         $chirp
     * @return \Illuminate\Http\Response
     */
    // [tl! collapse:end]
    public function destroy(Request $request, Chirp $chirp)
    {
        $this->authorize('delete', $chirp);

        $chirp->delete();

        if ($request->wantsTurboStream()) {
        if ($request->wantsTurboStream() && ! $request->wasFromTurboNative()) { // [tl! remove:-1,1 add]
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
}
```

That should have our edit and delete flows working properly, which is cool!

## Using a Native Modal Screen for Forms

Instead of showing the edit form in a new screen, I think it would be cool to introduce a native modal screen that other pages could use as well. First, add a new Fragment called `WebModalFragment` under the `web` package in the Android project without a layout:

```kotlin
package com.example.turbochirpernative.features.web

import android.os.Bundle
import android.view.View
import com.example.turbochirpernative.util.displayBackButtonAsCloseIcon
import dev.hotwire.turbo.nav.TurboNavGraphDestination

@TurboNavGraphDestination(uri = "turbo://fragment/web/modal")
class WebModalFragment : WebFragment() {
    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)
        initToolbar()
    }

    private fun initToolbar() {
        toolbarForNavigation()?.displayBackButtonAsCloseIcon()
    }
}
```

We need to create a new Extensions.kt file inside the `util` package so we can add this `displayBackButtonAsCloseIcon` method to the Toolbar:

```kotlin
package com.example.turbochirpernative.util

import androidx.appcompat.widget.Toolbar
import androidx.core.content.ContextCompat
import com.example.turbochirpernative.R

fun Toolbar.displayBackButtonAsCloseIcon() {
    navigationIcon = ContextCompat.getDrawable(context, R.drawable.ic_close)
}
```

Next, add a new Vector to the drawables by right-clicking on "New -> Vector Asset" inside the "res/drawables" folder, name it `ic_close` and choose the close icon.

Then, let's register the `WebModalFragment` in our `MainSessionNavHostFragment`:

```kotlin
package com.example.turbochirpernative.main
// [tl! collapse:start]
import android.webkit.JavascriptInterface
import android.webkit.WebView
import android.widget.Button
import android.widget.LinearLayout
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.fragment.app.Fragment
import com.example.turbochirpernative.BuildConfig
import com.example.turbochirpernative.R // [tl! collapse:end]
import com.example.turbochirpernative.features.auth.LoginFragment
import com.example.turbochirpernative.features.web.ChirpsHomeFragment
import com.example.turbochirpernative.features.web.WebFragment
import com.example.turbochirpernative.features.web.WebModalFragment // [tl! add]
// [tl! collapse:start]
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
    // [tl! collapse:end]
    override val registeredFragments: List<KClass<out Fragment>>
        get() = listOf(
            WebFragment::class,
            WebModalFragment::class, // [tl! add]
            LoginFragment::class,
            ChirpsHomeFragment::class,
        )
    // [tl! collapse:start]
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

    override fun showToast(msg: String) {
        activity?.runOnUiThread {
            Toast
                .makeText(requireContext(), msg, Toast.LENGTH_SHORT)
                .show()
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

interface PopupMenuDelegator {
    fun showPopupMenu(options: PopupMenu);
    fun showToast(msg: String);
}

class JsBridge(private var delegator: PopupMenuDelegator) {
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

    @JavascriptInterface
    fun showToast(msg: String) {
        delegator.showToast(msg)
    }
}
// [tl! collapse:end]
```

We need to add a new entry to our configuration so any URI ending with `/edit` or `/create` (our forms), will open inside of a web modal fragment instead of the default web fragment:

```json
{
  "settings": {
    "screenshots_enabled": true
  },
  "rules": [
    {
      "patterns": [
        ".*"
      ],
      "properties": {
        "context": "default",
        "uri": "turbo://fragment/web",
        "pull_to_refresh_enabled": true
      }
    },
    {
      "patterns": [
        "/edit$",
        "/edit/$",
        "/create$",
        "/create/$"
      ],
      "properties": {
        "context": "default",
        "uri": "turbo://fragment/web/modal",
        "pull_to_refresh_enabled": false
      }
    },
    {
      "patterns": [
        "login$",
        "login/$"
      ],
      "properties": {
        "context": "default",
        "uri": "turbo://fragment/auth/login",
        "pull_to_refresh_enabled": false
      }
    },
    {
      "patterns": [
        "chirps$",
        "chirps/$"
      ],
      "properties": {
        "context": "default",
        "uri": "turbo://fragment/chirps/index",
        "pull_to_refresh_enabled": true
      }
    }
  ]
}
```

Let's also hide the cancel button when inside a Turbo Native client by updating our `resources/chirps/_form.blade.php` in our web app:

```blade
<form action="{{ ($chirp ?? false) ? route('chirps.update', $chirp) : route('chirps.store') }}" method="POST">
    <!-- [tl! collapse:start] -->
    @if ($chirp ?? false)
        @method('PUT')
    @endif

    <textarea
        name="message"
        placeholder="What's on your mind?"
        class="block w-full border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm"
    >{{ $chirp->message ?? '' }}</textarea>
    <x-input-error :messages="$errors->get('message')" class="mt-2" />
    <!-- [tl! collapse:end] -->
    <div class="flex items-center justify-start space-x-2">
        <x-primary-button class="mt-4">
            {{ __('Chirp') }}
        </x-primary-button>

        @if ($chirp ?? false)
        <a href="{{ route('chirps.index') }}" class="mt-4">Cancel</a>
        <a href="{{ route('chirps.index') }}" class="mt-4 turbo-native:hidden">Cancel</a><!-- [tl! remove:-1,1 add] -->
        @endif
    </div>
</form>
```

## Testing It Out

Now, our app should be a bit nicer:

![Edit Chirp as modal](/images/native/web-modal-fragment.png)

[Continue to Chirps Delete Confirmation...](/native-deleting-confirmation)
