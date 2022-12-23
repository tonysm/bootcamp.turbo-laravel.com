# Native Floating Action Button to Create Chirps

[TOC]

## Introduction

Rendering the create chirps form inline right on the homepage isn't the best UX for mobile. Instead, it would be better to display the form as a native modal screen. Let's implement that, but first, let's hide the entire create chirps form on Turbo Native.

## Hiding the Elements for Turbo Native only

We could technically prevent the entire section from even rendering on requests made by Turbo Native clients using the `@unlessturbonative` Blade directives, something like this:

```blade
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Chirps') }}
        </h2>
    </x-slot>

    <div class="max-w-2xl mx-auto p-4 sm:p-6 lg:p-8">
        @unlessturbonative<!-- [tl! add] -->
        <x-turbo-frame id="create_chirp" src="{{ route('chirps.create') }}">
            <div class="relative flex items-center justify-center py-10 px-4 rounded-lg border border-dotted border-gray-300">
                <a class="text-gray-700" href="{{ route('chirps.create') }}">
                    Add a new Chirp
                    <span class="absolute inset-0"></span>
                </a>
            </div>
        </x-turbo-frame>
        @endunlessturbonative<!-- [tl! add] -->

        <div id="chirps" class="mt-6 bg-white shadow-sm rounded-lg divide-y">
            @each('chirps._chirp', $chirps, 'chirp')
        </div>
    </div>
</x-app-layout>
```

This would work, but that makes this page harder to cache. Not a problem now, but I prefer doing things like that on the client-side with CSS and a bit of JS.

Since we have configured our WebView to use a custom `User-Agent` header, we can actually detect when our webapp is running inside a Turbo Native client by checking that. Let's first add a helper to check the platform by creating a `resources/js/helpers/platform.js` file with the following contents:

```js
const { userAgent } = window.navigator;

export const isIos = /iPhone|iPad/.test(userAgent)
export const isAndroid = /Android/.test(userAgent)
export const isMobile = isIos || isAndroid

export const isIosApp = /Turbo Native iOS/.test(userAgent)
export const isAndroidApp = /Turbo Native Android/.test(userAgent)
export const isMobileApp = isIosApp || isAndroidApp
```

Now, let's update our `resources/js/app.js` file to add a `turbo-native` class to out HTML document:

```js
import './bootstrap';
import './elements/turbo-echo-stream-tag';
import './libs';
import '@github/time-elements';
import { isMobileApp } from './helpers/platform'; // [tl! add:start]

if (isMobileApp) {
    document.documentElement.classList.add('turbo-native');
}
// [tl! add:end]
```

This will ensure that when our webapp runs inside a Turbo Native client, a `.turbo-native` class will be added to the `<html>` element in our page, but we're not doing anything yet with it. Let's create a custom TailwindCSS modifier that will allow us to make things behave differently when the `.turbo-native` class is present in the document.

To do that, open the `tailwind.config.js` file in the root of your Laravel app and make the following changes:

```js filename=tailwind.config.js
const defaultTheme = require('tailwindcss/defaultTheme');
const plugin = require('tailwindcss/plugin'); // [tl! add]

/** @type {import('tailwindcss').Config} */
module.exports = {
    // [tl! collapse:start]
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Nunito', ...defaultTheme.fontFamily.sans],
            },
            keyframes: {
                'appear-then-fade': {
                    '0%, 100%': {
                        opacity: 0,
                    },
                    '5%, 60%': {
                        opacity: 1,
                    },
                }
            },
            animation: {
                ['appear-then-fade']: 'appear-then-fade 4s ease-in-out both',
            },
        },
    },
    // [tl! collapse:end]
    plugins: [
        require("@tailwindcss/forms"),
        // [tl! add:start]
        plugin(function ({ addVariant }) {
            return addVariant('turbo-native', ['&.turbo-native', '.turbo-native &']);
        }),
        // [tl! add:end]
    ],
};
```

With that, we can use the new modifier like any other default modifier in Tailwind. Let's use it to hide the create chirps form on the index page for Turbo Native clients. Open the `resources/views/chirps/index.blade.php` and make the following changes:

```blade filename=resources/views/chirps/index.blade.php
<x-app-layout>
    <!-- [tl! collapse:start] -->
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Chirps') }}
        </h2>
    </x-slot>
    <!-- [tl! collapse:end] -->
    <div class="max-w-2xl mx-auto p-4 sm:p-6 lg:p-8">
        <x-turbo-frame id="create_chirp" src="{{ route('chirps.create') }}">
        <x-turbo-frame id="create_chirp" src="{{ route('chirps.create') }}" class="turbo-native:hidden" loading="lazy"><!-- [tl! remove:-1,1 add] -->
            <div class="relative flex items-center justify-center py-10 px-4 rounded-lg border border-dotted border-gray-300">
                <a class="text-gray-700" href="{{ route('chirps.create') }}">
                    Add a new Chirp
                    <span class="absolute inset-0"></span>
                </a>
            </div>
        </x-turbo-frame>

        <div id="chirps" class="mt-6 bg-white shadow-sm rounded-lg divide-y">
            @each('chirps._chirp', $chirps, 'chirp')
        </div>
    </div>
</x-app-layout>
```

![Hiding the Create Chirps form](/images/native/fab-chirps-hide-form.png)

Let's also tweak our index page a bit to remove some padding and unnecessary margins:

```blade filename=resources/views/chirps/index.blade.php
<x-app-layout>
    <!-- [tl! collapse:start] -->
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Chirps') }}
        </h2>
    </x-slot>
    <!-- [tl! collapse:end] -->
    <div class="max-w-2xl mx-auto p-4 sm:p-6 lg:p-8">
    <div class="max-w-2xl mx-auto p-4 sm:p-6 lg:p-8 turbo-native:p-0"><!-- [tl! remove:-1,1 add] -->
        <x-turbo-frame id="create_chirp" src="{{ route('chirps.create') }}" class="turbo-native:hidden" loading="lazy">
            <!-- [tl! collapse:start ]-->
            <div class="relative flex items-center justify-center py-10 px-4 rounded-lg border border-dotted border-gray-300">
                <a class="text-gray-700" href="{{ route('chirps.create') }}">
                    Add a new Chirp
                    <span class="absolute inset-0"></span>
                </a>
            </div>
            <!-- [tl! collapse:end] -->
        </x-turbo-frame>

        <div id="chirps" class="mt-6 bg-white shadow-sm rounded-lg divide-y">
        <div id="chirps" class="mt-6 bg-white shadow-sm rounded-lg divide-y turbo-native:mt-0"><!-- [tl! remove:-1,1 add] -->
            @each('chirps._chirp', $chirps, 'chirp')
        </div>
    </div>
</x-app-layout>
```

Let's also hide the web nav bar for Turbo Native users, our navigation should be fully native on the mobile clients anyways. To do that, change the `resources/views/layouts/navigation.blade.php` file:

```blade filaname=resources/views/layouts/navigation.blade.php
<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
<nav x-data="{ open: false }" class="bg-white border-b border-gray-100 turbo-native:hidden"><!-- [tl! remove:-1,1 add] -->
    <!-- [tl! collapse:start] -->
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-10 w-auto fill-current text-gray-600" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>
                    <x-nav-link :href="route('chirps.index')" :active="request()->routeIs('chirps.*')">
                        {{ __('Chirps') }}
                    </x-nav-link>
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ml-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="flex items-center text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ml-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-mr-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('chirps.index')" :active="request()->routeIs('chirps.*')">
                {{ __('Chirps') }}
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
    <!-- [tl! collapse:end] -->
</nav>
```

Let's also hide the header section in the `resources/views/layouts/app.blade.php` layout file:

```blade filename=resources/views/layouts/app.blade.php
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <!-- [tl! collapse:start] -->
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="stylesheet" href="https://fonts.bunny.net/css2?family=Nunito:wght@400;600;700&display=swap">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <!-- [tl! collapse:end] -->
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')
            @include('layouts.notifications')

            <!-- Page Heading -->
            <header class="bg-white shadow">
            <header class="bg-white shadow turbo-native:hidden"><!-- [tl! remove:-1,1 add] -->
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
            <!-- [tl! collapse:start] -->
            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
            <!-- [tl! collapse:end] -->
        </div>
    </body>
</html>
```

And our page should look like this:

![Turbo Native UI Tweaks](/images/native/fab-ui-tweaks.png)

## Adding the Floating Action Button

Now that the create chirps form is hidden, we need to allow our users to somehow navigate to the create chirps form. Let's create our custom `ChirpsHomeFragment` that will be specific to the `chirps.index` route.

Create a new Kotlin class inside the `features.web` package and call it `ChirpsHomeFragment`:

```kotlin
package com.example.turbochirpernative.features.web

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import com.example.turbochirpernative.R
import dev.hotwire.turbo.nav.TurboNavGraphDestination

@TurboNavGraphDestination(uri = "turbo://fragment/chirps/index")
class ChirpsHomeFragment: WebFragment() {
    override fun onCreateView(inflater: LayoutInflater, container: ViewGroup?, savedInstanceState: Bundle?): View? {
        return inflater.inflate(R.layout.fragment_chirps_home, container, false)
    }
}
```

Notice that we're extending the `WebFragment` class so we need to make it open:

```kotlin
// [tl! collapse:start]
package com.example.turbochirpernative.features.web

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import com.example.turbochirpernative.R
import dev.hotwire.turbo.fragments.TurboWebFragment
import dev.hotwire.turbo.nav.TurboNavGraphDestination
// [tl! collapse:end]
@TurboNavGraphDestination(uri = "turbo://fragment/web")
class WebFragment: TurboWebFragment() {
open class WebFragment: TurboWebFragment() { // [tl! remove:-1,1 add]
    // [tl! collapse:start]
    override fun onCreateView(inflater: LayoutInflater, container: ViewGroup?, savedInstanceState: Bundle?): View? {
        return inflater.inflate(R.layout.fragment_web, container, false)
    }

    override fun onVisitCompleted(location: String, completedOffline: Boolean) {
        super.onVisitCompleted(location, completedOffline)

        val script = "window.NativeBridge.start();"
        session.webView.evaluateJavascript(script, null)
    }
    // [tl! collapse:end]
}
```

Also, we're rendering a different layout file, so we need to create it. Add a new layout file by right-clicking on the `res/layout` folder and choosing the "New -> Layout Resource File" option in the menu, add the following contents to it:

```xml
<?xml version="1.0" encoding="utf-8"?>
<androidx.constraintlayout.widget.ConstraintLayout
    xmlns:android="http://schemas.android.com/apk/res/android"
    xmlns:app="http://schemas.android.com/apk/res-auto"
    xmlns:tools="http://schemas.android.com/tools"
    android:layout_width="match_parent"
    android:layout_height="match_parent">

    <com.google.android.material.appbar.AppBarLayout
        android:id="@+id/app_bar"
        android:layout_width="match_parent"
        android:layout_height="wrap_content"
        android:background="?colorPrimary"
        app:layout_constraintEnd_toEndOf="parent"
        app:layout_constraintStart_toStartOf="parent"
        app:layout_constraintTop_toTopOf="parent">

        <com.google.android.material.appbar.MaterialToolbar
            android:id="@+id/toolbar"
            android:layout_width="match_parent"
            app:titleTextColor="?colorOnPrimary"
            android:layout_height="wrap_content" />

    </com.google.android.material.appbar.AppBarLayout>

    <include
        layout="@layout/turbo_view"
        android:layout_width="match_parent"
        android:layout_height="0dp"
        app:layout_constraintBottom_toBottomOf="parent"
        app:layout_constraintTop_toBottomOf="@+id/app_bar" />

    <com.google.android.material.floatingactionbutton.FloatingActionButton
        android:id="@+id/floatingActionButton"
        android:layout_width="wrap_content"
        android:layout_height="wrap_content"
        android:layout_margin="16dp"
        android:clickable="true"
        android:focusable="true"
        android:tint="@color/white"
        app:tint="@color/white"
        app:backgroundTint="@color/indigo_500"
        app:layout_constraintBottom_toBottomOf="parent"
        app:layout_constraintEnd_toEndOf="parent"
        app:srcCompat="@android:drawable/ic_input_add"
        android:contentDescription="New Chirp"
    />

</androidx.constraintlayout.widget.ConstraintLayout>
```

Now, let's register our new fragment in the `MainSessionNavHostFragment`:

```kotlin
// [tl! collapse:start]
package com.example.turbochirpernative.main

import android.webkit.WebView
import androidx.appcompat.app.AppCompatActivity
import androidx.fragment.app.Fragment
import com.example.turbochirpernative.BuildConfig
// [tl! collapse:end]
import com.example.turbochirpernative.features.auth.LoginFragment
import com.example.turbochirpernative.features.web.ChirpsHomeFragment // [tl! add]
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
            LoginFragment::class,
            ChirpsHomeFragment::class, // [tl! add]
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

Now, let's configure our route path in the `assets/json/configuration.json` file to open that fragment whenever we visit the `chirps.index` route:

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

Notice that the URI matches what we defined in the `ChirpsHomeFragment`: `turbo://fragment/chirps/index`.

At this point, our app looks like this:

![With The FAB](/images/native/fab-showing-up.png)

But our button doesn't work yet. Let's tell Turbo Native to make a visit to the `chirps/create` route. First, add the new constant to the `util.Constants` file:

```kotlin
package com.example.turbochirpernative.util

const val BASE_URL = "http://10.0.2.2"
const val CHIRPS_HOME_URL = "$BASE_URL/chirps"
const val CHIRPS_CREATE_URL = "$CHIRPS_HOME_URL/create" // [tl! add]

const val API_BASE_URL = "$BASE_URL/api"
const val API_CSRF_COOKIES_URL = "$BASE_URL/sanctum/csrf-cookie"
const val API_LOGIN_URL = "$API_BASE_URL/login"
```

Now, change the `ChirpsHomeFragment` to setup the click handler on that fab:

```kotlin
package com.example.turbochirpernative.features.web

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View // [tl! add]
import android.view.ViewGroup
import com.example.turbochirpernative.R
import com.example.turbochirpernative.util.CHIRPS_CREATE_URL
import com.google.android.material.floatingactionbutton.FloatingActionButton
import dev.hotwire.turbo.nav.TurboNavGraphDestination

@TurboNavGraphDestination(uri = "turbo://fragment/chirps/index")
class ChirpsHomeFragment: WebFragment() {
    // [tl! collapse:start]
    override fun onCreateView(inflater: LayoutInflater, container: ViewGroup?, savedInstanceState: Bundle?): View? {
        return inflater.inflate(R.layout.fragment_chirps_home, container, false)
    }
    // [tl! collapse:end add:start]
    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        setupFab()
    }

    private fun setupFab() {
        view?.findViewById<FloatingActionButton>(R.id.createChirpsFab)?.setOnClickListener {
            navigate(CHIRPS_CREATE_URL)
        }
    }
    // [tl! add:end]
}
```

Now, let's click on it and you should be redirected to the create chirps form! How cool is that?!

![Create Chirps Form](/images/native/fab-create-chirps-form-in-new-page.png)

If you try creating a chirp, however, you should see some interesting behavior...

![Wrong behavior after creating chirps](/images/native/fab-wrong-behavior-after-creating.png)

That's not good. That's because we're returning Turbo Streams on the `ChirpController@store` action. Let's change it so it doesn't do that when the request was done via a Turbo Native client. We want the redirect there. Head to the `app/Http/Controllers/ChirpController.php` file and change the `store` action like so:

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
                ]))
            ]);
        }

        return redirect()
            ->route('chirps.index')
            ->with('status', __('Chirp created.'));
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

This should redirect us to the home page after creating a chirp and the new Chirp should appear there! Cool.

We have lost the flash message, but we'll handle that soon. One thing is bothering me: we're showing the "Turbo Chirper Native" title on every screen. I don't like that. Instead, I want each screen to customize the title. Well, it turns out it already does that based on the title of the page we're visiting. Let's change our `app.blade.php` layout file to accept a `$title` prop:

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <!-- [tl! collapse:start] -->
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <!-- [tl! collapse:end] -->
        <title>{{ config('app.name', 'Laravel') }}</title>
        <title>{{ $title ?? config('app.name', 'Laravel') }}</title><!-- [tl! remove:-1,1 add] -->
        <!-- [tl! collapse:start] -->
        <!-- Fonts -->
        <link rel="stylesheet" href="https://fonts.bunny.net/css2?family=Nunito:wght@400;600;700&display=swap">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <!-- [tl! collapse:end] -->
    </head>
    <!-- [tl! collapse:start] -->
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')
            @include('layouts.notifications')

            <!-- Page Heading -->
            <header class="bg-white shadow turbo-native:hidden">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>
    </body>
    <!-- [tl! collapse:end] -->
</html>
```

Now, let's register the prop in the `AppLayout` component in `app/View/Components`:

```php
<?php
// [tl! collapse:start]
namespace App\View\Components;

use Illuminate\View\Component;
// [tl! collapse:end]
class AppLayout extends Component
{
    // [tl! add:start]
    public function __construct(public ?string $title = null)
    {
    }
    // [tl! add:end]
    // [tl! collapse:start]
    /**
     * Get the view / contents that represents the component.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('layouts.app');
    }
    // [tl! collapse:end]
}
```

Then, in the `resources/views/chirps/create.blade.php` file, set the `:title` prop in the `<x-app-layout>` component:

```blade
<x-app-layout>
<x-app-layout :title="__('Create Chirp')"><!-- [tl! remove:-1,1 add] -->
    <!-- [tl! collapse:start] -->
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <a href="{{ route('chirps.index') }}" class="underline underline-offset-2 text-indigo-600">Chirps</a> <span class="text-gray-300">/</span> {{ __('New Chirp') }}
        </h2>
    </x-slot>

    <div class="max-w-2xl mx-auto p-4 sm:p-6 lg:p-8">
        <x-turbo-frame id="create_chirp" target="_top">
            @include('chirps._form')
        </x-turbo-frame>
    </div>
    <!-- [tl! collapse:end] -->
</x-app-layout>
```

And with that, our create chirps page should have the "Create Chirp" title:

![Create Chirp Screen with Title](/images/native/fab-create-chirps-with-title.png)

[Continue to editing chirps on new screens...](/native-editing-modal)
