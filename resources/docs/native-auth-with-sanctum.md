# Native Auth Screens and Laravel Sanctum

Let's tackle the mobile authentication first. We're now able to use the web authentication flow, but as we discussed earlier, sometimes we need to implement fully native screens in our app that uses JSON endpoints with an Access Token. For that reason, we're gonna change our login flow just for our Turbo Native Android client. If you were building a Native iOS app, that could also use this same flow.

Laravel has essentially two first-party packages when it comes to Token-based authentication: [Laravel Sanctum](https://laravel.com/docs/9.x/sanctum) and [Laravel Passport](https://laravel.com/docs/passport).

For this Bootcamp, we're gonna be using Laravel Sanctum, as our flow is a mix of an SPA and mobile authentication flows, both provided by Sanctum.

Oh, and we're going to use [Jetpack Compose](https://developer.android.com/jetpack/compose) to build the Login screen. I don't know about you, but building screens in XML ain't I'd be proud to show you. Let's start by adding the Gradle dependencies we need and making some tweaks to our app so Compose works properly.

## Adding Jetpack Compose

Open the Module's `build.gradle` file and add the following lines to it:

```groovy
plugins {
    // [tl! collapse:start]
    id 'com.android.application'
    id 'org.jetbrains.kotlin.android'
    // [tl! collapse:end]
}

android {
    // [tl! collapse:start]
    namespace 'com.example.turbochirpernative'
    compileSdk 33

    defaultConfig {
        applicationId "com.example.turbochirpernative"
        minSdk 24
        targetSdk 33
        versionCode 1
        versionName "1.0"

        testInstrumentationRunner "androidx.test.runner.AndroidJUnitRunner"
    }

    buildTypes {
        release {
            minifyEnabled false
            proguardFiles getDefaultProguardFile('proguard-android-optimize.txt'), 'proguard-rules.pro'
        }
    }
    compileOptions {
        sourceCompatibility JavaVersion.VERSION_1_8
        targetCompatibility JavaVersion.VERSION_1_8
    }
    kotlinOptions {
        jvmTarget = '1.8'
    }
    // [tl! collapse:end add:start]
    buildFeatures {
        viewBinding true
        compose true
    }
    composeOptions {
        kotlinCompilerExtensionVersion "1.4.0-dev-k1.7.20-RC-a143c065804"
    }
    // [tl! add:end]
}

dependencies {
    def lifecycle_version = '2.5.1'
    def compose_version = '1.3.0-rc01' // [tl! add]
    // [tl! collapse:start]
    implementation 'androidx.core:core-ktx:1.9.0'
    implementation 'androidx.appcompat:appcompat:1.5.1'
    implementation 'com.google.android.material:material:1.6.1'
    implementation 'androidx.constraintlayout:constraintlayout:2.1.4'
    // [tl! collapse:end]
    implementation 'dev.hotwire:turbo:7.0.0-rc12'
    implementation "androidx.lifecycle:lifecycle-livedata-ktx:$lifecycle_version"
    implementation "androidx.lifecycle:lifecycle-viewmodel-ktx:$lifecycle_version"
    implementation "androidx.lifecycle:lifecycle-runtime-ktx:$lifecycle_version"
    implementation "androidx.compose.ui:ui:$compose_version" // [tl! add:start]
    implementation "androidx.compose.material:material:$compose_version"
    implementation "com.squareup.okhttp3:okhttp:4.10.0"
    implementation "com.google.code.gson:gson:2.9.1" // [tl! add:end]
    testImplementation 'junit:junit:4.13.2'
    androidTestImplementation 'androidx.test.ext:junit:1.1.3'
    androidTestImplementation 'androidx.test.espresso:espresso-core:3.4.0'
}
```

We added a bunch of dependencies here, let's dissect that:

* The `androidx.compose.ui:ui:*` one is the main Jetpack Compose package
* The `androidx.compose.material:material:*` comes with a set of Material UI components we can use
* The `com.squareup.okhttp3:okhttp:4.10.0` is the lib we're gonna use to make HTTP requests from our Native screens
* The `com.google.code.gson:gson:2.9.1` is the lib we're gonna use to serialize our JSON responses to objects we can use in Kotlin

We also made some changes to the `buildFeatures` to enable compose and also configured the `composeOptions` to use a specific version Jetcompose Compose Compiler to work with the Kotlin version I have (which is `1.7.20-RC`). I was getting weird compilation errors without this. It was after many attempts that I found this fix (again, I'm no mobile Android expert). If you happen to use a different Kotlin version, make sure you visit the [compatibility table to see which version of the Compose Compiler you need](https://androidx.dev/storage/compose-compiler/repository).

For that compiler customization to work, we need to make a change to our `settings.gradle` file to add the compose compiler's repository to Maven:

```groovy
pluginManagement {
    // [tl! collapse:start]
    repositories {
        gradlePluginPortal()
        google()
        mavenCentral()
    }
    // [tl! collapse:end]
}
dependencyResolutionManagement {
    repositoriesMode.set(RepositoriesMode.FAIL_ON_PROJECT_REPOS)
    repositories {
        google()
        mavenCentral()
        // [tl! add:start]
        maven {
            url "https://androidx.dev/storage/compose-compiler/repository/"
        }
        // [tl! add:end]
    }
}
// [tl! collapse:start]
rootProject.name = "Turbo Chirper Native"
include ':app'
// [tl! collapse:end]
```

Okay, now press that "Sync now" link at the top and fingers crossed!

![Gradle Dependencies](/images/native/auth-gradle-dependencies.png)

## The Auth Client Service

Now that we have all the libs in place, let's start sketching out how this is gonna work.

As mentioned earlier, we're gonna need both the [Cookie](https://laravel.com/docs/9.x/sanctum#spa-authentication) AND the [Token](https://laravel.com/docs/9.x/sanctum#mobile-application-authentication) authentication in our case. We need the Cookie-based authentication for our shared WebView while the Access Token we'll store in our app settings so we can reuse it whenever we need to build a fully native screen.

We'll handle the Laravel side of this soon. Add these new constants to our `util.Constants` file:

```kotlin
package com.example.turbochirpernative.util

const val BASE_URL = "http://10.0.2.2"
const val CHIRPS_HOME_URL = "$BASE_URL/chirps"
// [tl! add:start]
const val API_BASE_URL = "$BASE_URL/api"
const val API_CSRF_COOKIES_URL = "$BASE_URL/sanctum/csrf-cookie"
const val API_LOGIN_URL = "$API_BASE_URL/login"
// [tl! add:end]
```

Next, let's create our `AuthClient` to interact with the API. First, add an "api" package to the root of the project (next to the "features" package) by right-clicking on the root package then choosing "New > Package". Inside of it, add a new Kotlin class:

```kotlin
package com.example.turbochirpernative.api

import android.util.Log
import android.webkit.CookieManager
import com.example.turbochirpernative.util.API_CSRF_COOKIES_URL
import com.example.turbochirpernative.util.API_LOGIN_URL
import com.example.turbochirpernative.util.BASE_URL
import com.google.gson.Gson
import com.google.gson.JsonObject
import okhttp3.*
import okhttp3.HttpUrl.Companion.toHttpUrl
import okhttp3.RequestBody.Companion.toRequestBody
import okio.IOException
import java.net.URLDecoder

class AuthClient() {
    private var CLIENT_USER_AGENT = "Turbo Native Android HTTP Client"

    private var csrfToken: String = ""
    private var csrfCookieStored: String = ""
    private var sessionCookieStored: String = ""

    @Throws(IOException::class)
    fun fetchCsrfToken(onCsrfTokenFetched: () -> Unit) {
        val client = OkHttpClient()

        val request: Request = Request.Builder()
            .url(API_CSRF_COOKIES_URL)
            .addHeader("Content-Type", "application/json; charset=utf-8")
            .addHeader("Accept", "application/json; charset=utf-8")
            .addHeader("User-Agent", CLIENT_USER_AGENT)
            .get()
            .build()

        client.newCall(request).enqueue(object: Callback {
            override fun onFailure(call: Call, e: java.io.IOException) {
                e.printStackTrace()
            }

            override fun onResponse(call: Call, response: Response) {
                response.use {
                    val cookieManager = CookieManager.getInstance()
                    val cookies = Cookie.parseAll(BASE_URL.toHttpUrl(), it.headers)

                    cookies.forEach { cookie ->
                        if (cookie.name == "XSRF-TOKEN") {
                            csrfToken = URLDecoder.decode(cookie.value.toString(), Charsets.UTF_8.name())
                            csrfCookieStored = cookie.toString()
                        } else if (cookie.name == "turbo_chirp_native_session") {
                            sessionCookieStored = cookie.toString()
                        }

                        cookieManager.setCookie(BASE_URL, cookie.toString())
                    }
                }

                onCsrfTokenFetched()
            }
        })
    }

    @Throws(IOException::class)
    fun attempt(email: String, password: String, onResponse: (AuthResponse) -> Unit) {
        val client = OkHttpClient()

        val body = JsonObject()
        body.addProperty("email", email)
        body.addProperty("password", password)

        val request: Request = Request.Builder()
            .url(API_LOGIN_URL)
            .addHeader("Content-Type", "application/json; charset=utf-8")
            .addHeader("Accept", "application/json; charset=utf-8")
            .addHeader("User-Agent", CLIENT_USER_AGENT)
            .addHeader("X-XSRF-TOKEN", csrfToken)
            .addHeader("Cookie", "$sessionCookieStored; $csrfCookieStored")
            .post(body.toString().toRequestBody())
            .build()

        client.newCall(request).enqueue(object: Callback {
            override fun onFailure(call: Call, e: java.io.IOException) {
                e.printStackTrace()
            }

            override fun onResponse(call: Call, response: Response) {
                response.use {
                    val gson = Gson()

                    when {
                        it.isSuccessful -> {
                            Log.i("AuthClient", "HTTP request worked!")

                            val cookieManager = CookieManager.getInstance()
                            val cookies = Cookie.parseAll(BASE_URL.toHttpUrl(), it.headers)

                            cookies.forEach { cookie ->
                                Log.i("AuthClient", "Received cookie " + cookie.name + " adding to the CookieManager...")

                                cookieManager.setCookie(BASE_URL, cookie.toString())
                            }

                            val login = gson.fromJson(it.body?.string(), LoginSuccessful::class.java)

                            onResponse(AuthResponse(
                                ok = true,
                                successResponse = login,
                                failedResponse = null,
                            ))
                        }
                        else -> {
                            Log.i("AuthClient", "HTTP request failed!")

                            onResponse(AuthResponse(
                                ok = false,
                                failedResponse = gson.fromJson(it.body?.string(), FailedResponse::class.java),
                                successResponse = null,
                            ))
                        }
                    }
                }
            }
        })
    }
}

data class AuthResponse(
    var ok: Boolean,
    var successResponse: LoginSuccessful?,
    var failedResponse: FailedResponse?,
)

data class UserData(
    val name: String,
)

data class LoginSuccessful(
    val token: String,
    val data: UserData,
    val redirectTo: String?,
)

data class InvalidLoginResponse(
    val email: Array<String>?,
    val password: Array<String>?,
)

data class FailedResponse(
    val message: String,
    val errors: InvalidLoginResponse,
)
```

I know there's a lot here. I'm not that good in Kotlin, but this is not the point of this bootcamp. We're essentially adding a method to fetch the CSRF token used to get the Cookie authentication working, which we can use like so:

```kotlin
AuthClient().fetchCsrfToken {
    // It worked, do something after...
}
```

This method will make an HTTP call to the `/sanctum/csrf-cookie` route provided by Sanctum. That route will return the Cookies we need in order to successfully authenticate, since we're gonna use the CSRF protection middleware from Laravel in the API routes.

We'd need a way to store the cookies so they'd automatically be added to the `CookieManager` whenever a response is received by the HTTP Client in whatever context, not just for the auth one. Anyways, for our purposes, this will do.

This code also adds the method to attempt authenticating, which should call an endpoint at `/api/login`, which will create soon when we're handling the Laravel side of things. This is how the `attempt` method may be used:

```kotlin
AuthClient().attempt(email, password) { it ->
    // "it" in this case is an instance of the `AuthResponse` class
    // we created in the same file as the `AuthClient`...
}
```

With that in place, let's build the Login Screen!

## The Native Login Screen

Create the `features.auth` package. And, before we create the `LoginFragment`, we'll add two interfaces that it's gonna use. First, add a `CsrfTokenCallback` interface to the `features.auth` package with the following contents:

```kotlin
package com.example.turbochirpernative.features.auth

interface CsrfTokenCallback {
    fun onCsrfTokenFetched()
}
```

Then, add a `LoginRequestCallback` interface also in the `features.auth` package:

```kotlin
package com.example.turbochirpernative.features.auth

interface LoginRequestCallback {
    fun onLoginSucceeded(url: String)
    fun onLoginFailed(msg: String)
}
```

Next, add the `LoginFragment` also to the `features.auth` package:

```kotlin

package com.example.turbochirpernative.features.auth

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.Toast
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.material.Button
import androidx.compose.material.MaterialTheme
import androidx.compose.material.OutlinedTextField
import androidx.compose.material.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.DisposableEffect
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.ComposeView
import androidx.compose.ui.text.TextStyle
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.text.input.TextFieldValue
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.turbochirpernative.api.AuthClient
import dev.hotwire.turbo.fragments.TurboFragment
import dev.hotwire.turbo.nav.TurboNavGraphDestination

@TurboNavGraphDestination(uri = "turbo://fragment/auth/login")
class LoginFragment : TurboFragment(), LoginRequestCallback, CsrfTokenCallback {
    private val authClient = AuthClient()

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        return ComposeView(requireContext()).apply {
            setContent { MaterialTheme {
                LoginScreen()
            } }
        }
    }

    @Composable
    private fun LoginScreen() {
        DisposableEffect(true) {
            fetchCsrfToken()

            onDispose {  }
        }

        Column(Modifier.fillMaxWidth(), horizontalAlignment = Alignment.CenterHorizontally, verticalArrangement = Arrangement.Center) {

            val email = remember { mutableStateOf(TextFieldValue("tonysm@hey.com")) }
            val password = remember { mutableStateOf(TextFieldValue("password")) }

            Text("Turbo Chirp Native", fontSize = 30.sp, style = TextStyle(fontWeight = FontWeight.Bold))
            Text("Login", fontSize = 24.sp, modifier = Modifier.padding(top = 10.dp))
            Column(modifier = Modifier.padding(vertical = 16.dp)) {
                OutlinedTextField(
                    label = { Text("Email") },
                    singleLine = true,
                    value = email.value,
                    onValueChange = { email.value = it }
                )
            }
            Column(modifier = Modifier.padding(vertical = 10.dp)) {
                OutlinedTextField(
                    label = { Text("Password") },
                    singleLine = true,
                    value = password.value,
                    onValueChange = { password.value = it },
                    visualTransformation = PasswordVisualTransformation(),
                )
            }
            Column(modifier = Modifier.padding(vertical = 10.dp)) {
                Button(onClick = {
                    if (! email.value.text.isEmpty() && ! password.value.text.isEmpty()) {
                        authClient.attempt(email.value.text, password.value.text) {
                            if (it.ok) {
                                onLoginSucceeded(it.successResponse?.redirectTo + "")
                            } else {
                                onLoginFailed(it.failedResponse?.message ?: "Something went wrong!")
                            }
                        }
                    }
                }) {
                    Text("Login")
                }
            }
        }
    }

    override fun onLoginSucceeded(url: String) {
        activity?.runOnUiThread { navigate(url) }
    }

    override fun onLoginFailed(msg: String) {
        activity?.runOnUiThread {
            Toast.makeText(context, msg, Toast.LENGTH_SHORT).show()
        }
    }

    override fun onCsrfTokenFetched() {
        activity?.runOnUiThread {
            Toast.makeText(context, "CSRF Token fetched!", Toast.LENGTH_SHORT).show()
        }
    }

    private fun fetchCsrfToken() {
        authClient.fetchCsrfToken() {
            onCsrfTokenFetched()
        }
    }
}

```

There's a lot going on in this screen, and we're not gonna talk about it much. The important thing to note here is that we're instanciating the `authClient` as an attribute to the `LoginFragment` instance. This is important because we're storing the CSRF Token, Cookie, and the Laravel Session cookie in the `AuthClient` instance, so we can't just create a new instance of the `AuthClient` whenever we want.

Also, we're calling the `authClient.fetchCsrfToken()` in a `DisposableEffect` to make sure it only calls it once when Compose mounts this screen. Then, whenever the user types non-blank email and password fields to the inputs we have just created, we'll call the `authClient.attempt()` with it. We're using Toast messages here and there to give some feedback on what's going on. That's more for us than for actual users of our app, so feel free to remove those if you want to.

Finally, let's register our fragment in the `MainSessionNavHostFragment`:

```kotlin
package com.example.turbochirpernative.main
// [tl! collapse:start]
import android.webkit.WebView
import androidx.appcompat.app.AppCompatActivity
import androidx.fragment.app.Fragment
import com.example.turbochirpernative.BuildConfig
// [tl! collapse:end]
import com.example.turbochirpernative.features.auth.LoginFragment // [tl! add]
import com.example.turbochirpernative.features.web.WebFragment
// [tl! collapse:start]
import com.example.turbochirpernative.util.CHIRPS_HOME_URL
import dev.hotwire.turbo.config.TurboPathConfiguration
import dev.hotwire.turbo.session.TurboSessionNavHostFragment
import kotlin.reflect.KClass
// [tl! collapse:end]
class MainSessionNavHostFragment : TurboSessionNavHostFragment() {
    // [tl! collapse:start]
    override val sessionName = "main"

    override val startLocation = CHIRPS_HOME_URL

    override val registeredActivities: List<KClass<out AppCompatActivity>>
        get() = listOf()
    // [tl! collapse:end]
    override val registeredFragments: List<KClass<out Fragment>>
        get() = listOf(
            WebFragment::class,
            LoginFragment::class, // [tl! add]
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
    }

    private fun customUserAgent(webView: WebView): String {
        return "Turbo Native Android ${webView.settings.userAgentString}"
    }
    // [tl! collapse:end]
}
```

Then, add the new entry to the `assets/json/configuration.json` to tell Turbo to render the `LoginFragment` whenever we're visiting the `/login` route:

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
        "login$",
        "login/$"
      ],
      "properties": {
        "context": "default",
        "uri": "turbo://fragment/auth/login",
        "pull_to_refresh_enabled": false
      }
    }
  ]
}
```

This is what the project structure should look like for you:

![Login Screen project structure](/images/native/auth-project-structure-login-screen.png)

Before we're able to test this, we need add Sanctum and our API routes to the Laravel side of our Chirper web app!

## Setting up Sanctum and the API Endpoints

Our Sanctum installation process will follow the docs with one exception. First thing is the Sanctum installation. In the chirper web app root folder, install Sanctum via Composer:

```bash
composer require laravel/sanctum
```

Next, publish the configuration file:

```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

Then, migrate the database:

```bash
php artisan migrate
```

Now is the exception part. Sanctum's default installation recommends adding the `EnsureFrontendRequestsAreStateful` middleware that ships with Sanctum at the top of the API route group middleware stack. Instead, we're gonna create our own. We're also adding the `TurboMiddleware` to that route group:

```php
<?php
// [tl! collapse:start]
namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;
// [tl! collapse:end]
class Kernel extends HttpKernel
{
    // [tl! collapse:start]
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array<int, class-string|string>
     */
    protected $middleware = [
        // \App\Http\Middleware\TrustHosts::class,
        \App\Http\Middleware\TrustProxies::class,
        \Illuminate\Http\Middleware\HandleCors::class,
        \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    ];
    // [tl! collapse:end]
    /**
     * The application's route middleware groups.
     *
     * @var array<string, array<int, class-string|string>>
     */
    protected $middlewareGroups = [
        'web' => [
            // [tl! collapse:start]
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            // [tl! collapse:end]
        ],

        'api' => [
            // \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \Tonysm\TurboLaravel\Http\Middleware\TurboMiddleware::class, // [tl! remove:-1,1 add]
            \App\Http\Middleware\EnsureTurboNativeRequestsAreStateful::class, // [tl! add]
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];
    // [tl! collapse:start]
    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array<string, class-string|string>
     */
    protected $routeMiddleware = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'signed' => \App\Http\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
    ];
    // [tl! collapse:end]
}
```

Now, let's create our own `EnsureFrontendRequestsAreStateful` in the `app/Http/Middleware/` folder:

```php
<?php

namespace App\Http\Middleware;

use Illuminate\Routing\Pipeline;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class EnsureTurboNativeRequestsAreStateful
{
    /**
     * Handle the incoming requests.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  callable  $next
     * @return \Illuminate\Http\Response
     */
    public function handle($request, $next)
    {
        $this->configureSecureCookieSessions();

        return (new Pipeline(app()))->send($request)->through($request->wasFromTurboNative() ? [
            function ($request, $next) {
                $request->attributes->set('turbo-native', true);

                return $next($request);
            },
            config('turbo-laravel.middleware.encrypt_cookies', \Illuminate\Cookie\Middleware\EncryptCookies::class),
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            config('turbo-laravel.middleware.verify_csrf_token', \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class),
        ] : [])->then(function ($request) use ($next) {
            return $next($request);
        });
    }

    /**
     * Configure secure cookie sessions.
     *
     * @return void
     */
    protected function configureSecureCookieSessions()
    {
        config([
            'session.http_only' => true,
            'session.same_site' => 'lax',
        ]);
    }
}
```

Now, let's add our login route to the `routes/api.php` entrypoint:

```php
<?php
// [tl! collapse:start]
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
// [tl! collapse:end]
// [tl! add:start]
Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (! Auth::attempt($credentials, true)) {
        throw ValidationException::withMessages([
            'email' => ['The provided credentials are incorrect.'],
        ]);
    }

    $request->session()->regenerate();

    return [
        'token' => Auth::user()->createToken('Turbo Native')->plainTextToken,
        'redirectTo' => route('chirps.index'),
        'data' => [
            'name' => Auth::user()->name,
        ],
    ];
});
// [tl! add:end]
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
```

Now, we're ready to test it!

## Testing Out

If you want to, purge all your sessions in the Laravel app by deleting all the files in the `storage/framework/sessions/` folder (assuming you're using the file session driver):

```bash
rm -f storage/framework/sessions/*
```

Now, go back to Android Studio and build the client again. You should see the login screen and if you authenticate, you should then be sent to the chirps home page!

![Native Login Screen](/images/native/auth-login-screen.png)

![Invalid Credentials](/images/native/auth-invalid-credentials.png)

![Chirps Home Page After Native Login](/images/native/auth-chirps-home.png)

Our access token is being stored in the preferences section of our app, which means we can reuse it whenever we need to make an HTTP call with a native screen and our WebView is successfully authenticated, so we don't need to worry about those screens that don't need native authentication anymore. Yay! Next, let's take a look at improving our mobile UX for creating Chirps.

[Continue to adding the Native Floating Action Button...](/native-fab-creating-chirps)
