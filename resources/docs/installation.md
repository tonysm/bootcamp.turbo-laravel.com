# Installation

Our first step is to create the web app and setup our local environment. There are two paths in here: one uses a local installation setup, and another one that uses [Laravel Sail](https://laravel.com/docs/sail). Choose how you're going to run the app locally as you feel more comfortable.

## Local Installation

If you'd rather have PHP installed locally and use SQLite, this section is for you. This local setup follows the same approach as the Official Laravel Bootcamp.

The first step is to create the project, which we can do using [Composer](https://getcomposer.org/):

```bash
composer create-project laravel/laravel turbo-chirper
```

Head over to the folder that was just created and start the Artisan serve command:

```bash
cd turbo-chirper/
php artisan serve
```

You should be able to see the welcome page for Laravel on your browser if you visit [http://localhost:8000](http://localhost:8000):

![Laravel Welcome page](/images/welcome-page.png)

Now, let's configure the app to use the SQLite database driver instead of the MySQL one. Open the `.env` file, delete all `DB_*` entries and replace it with the single connection one:

```env
DB_CONNECTION=sqlite # [tl! add]
DB_CONNECTION=mysql # [tl! remove:start]
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=bootcamp.turbo_laravel.com
DB_USERNAME=sail
DB_PASSWORD=password # [tl! remove:end]
```

Done!

## Laravel Sail

Laravel also has a containerized local development environment called [Laravel Sail](https://laravel.com/docs/sail). Let's assume you don't have PHP or Composer installed locally. To create the project, Laravel provides a build script hosted at [https://laravel.build](https://laravel.build/turbo-chirper) which we can use:

```bash
curl -s "https://laravel.build/turbo-chirper" | bash
```

We specify our project name as the first argument to the URI path there. This process may take some time as your container will get built locally.

By default, the installer will pre-configure Laravel Sail with a number of useful services for your local development, including a MySQL database server. You may [customize the Sail services](https://laravel.com/docs/installation#choosing-your-sail-services) if needed.

When the script is done running, you may head over to the created `turbo-chirper` folder:

```bash
cd turbo-chirper
./vendor/bin/sail up -d
```

When developing applications using Sail, you may execute Artisan, NPM, and Composer commands via the Sail CLI instead of invoking them directly:

```bash
./vendor/bin/sail php --version
./vendor/bin/sail artisan --version
./vendor/bin/sail composer --version
./vendor/bin/sail npm --version
```

Remember that when running the commands from now on.

Once the application's Docker containers have been started, you can access the application in your web browser at: [http://localhost](http://localhost).

![Welcome Page over Sail](/images/sail-welcome-page.png)

Done!

## Laravel Breeze

Before we start working on our features, we'll first need to handle Login and Registration. Luckily for us, Laravel has a set of Starterkits we can use. In this bootcamp, we're going to use Breeze because of its simplicity. Let's get that installed:

```bash
composer require laravel/breeze --dev
php artisan breeze:install
```

We're using the default Blade flavor of Breeze since it pairs nicely with Turbo. We're also using Laravel's frontend setup (for now) which relies on Vite. Let's compile our assets:

```bash
npm run dev
```

Finally, open up a new terminal, make sure you're in the `turbo-chirper/` project folder and run the migrations:

```bash
php artisan migrate
```

The welcome page should now have the Login and Register links at the top:

![Welcome with Auth](/images/install-welcome-auth.png)

And you should be able to head to the `/register` route and create your own account:

![Register Page](/images/install-register.png)

Then, you should be redirected to the Dashboard page:

![Dashboard Page](/images/install-dashboard.png)

This Dashboard page is protected by Laravel's auth middleware, so only authenticated users can access it. The registration process automatically authenticates us.

## Turbo Laravel

Let's install Turbo Laravel, 'cause this is a Turbo Bootcamp after all!

```bash
composer require tonysm/turbo-laravel
php artisan turbo:install --alpine
```

Since we're using Vite (for now), we need to install the NPM dependencies that were added to our `package.json` file and compile the assets again. If you still have the previous `npm run dev` command running, close it with `CTRL+C`, then run:

```bash
npm install
npm run dev
```

And that's it! Get to the Dashboard page, open the DevTools, go to the Console tab, type `Turbo` there and hit enter. You should see that the global Turbo object is there, which means Turbo was successfully installed!

![Turbo Installed](/images/turbo-installed.png)

## Importmap Laravel and TailwindCSS Laravel

To get things more interesting, let's install an alternative frontend setup that doesn't require having Node and NPM locally. We could stick with Vite, but I found it's hot code replacement feature not that great when working with Turbo. Feel free to skip this part of the tutorial if you want to keep using Vite.

We'll use [Importmap Laravel](https://github.com/tonysm/importmap-laravel) to handle the JS side of things:

```bash
composer require tonysm/importmap-laravel
```

Then, let's run the install command:

```bash
php artisan importmap:install
```

Now, let's create the symlink that will map our `resources/js/` folder to `public/js` so we can serve our local JS files to the browser. That's only needed when in local, by the way. In production you can use the `php artisan importmap:optimize` command. For now, all we have to do is run Laravel's `storage:link` command:

```bash
php artisan storage:link
```

If you're using Sail, remember to prefix this command with `./vendor/bin/sail`, since the symlink needs to be created inside the container.

Next, since we got rid of Vite, we need to install the TailwindCSS Laravel package to handle our CSS compilation:

```bash
composer require tonysm/tailwindcss-laravel
```

That's it. Let's download the TailwindCSS CLI binary and compile the assets the first time:

```bash
php artisan tailwindcss:install
```

This should update our guest and app layouts that Breeze created to add the link tag including the TailwindCSS file using the `tailwindcss()` function provided by the package.

Now, if you try refreshing the app with the console open, you'll see an error:

![Axios Error](/images/install-axios-error.png)

As of right now, it looks like Axios is not working correctly with ESM and Importmap. But we can use an older version that I know works for sure. Let's first unpin the axios dependency:

```bash
php artisan importmap:unpin axios
```

Now, let's pin it again but using the 0.27 version, which I know works:

```bash
php artisan importmap:pin axios@0.27
```

If you refresh the page now, the error should be gone and we're now using Importmap!

![Error Gone Importmap Welcome](/images/install-error-gone-importmap-welcome.png)

## Stimulus Laravel

Our last piece is replacing Alpine for Stimulus. Let's start by installing the [Stimulus Laravel](https://github.com/tonysm/stimulus-laravel) package:

```bash
composer require tonysm/stimulus-laravel
```

Next, let's run the install command:

```bash
php artisan stimulus:install
```

Let's change our main `app.js` file to import the `libs/index.js` file instead of each lib file and remove the Alpine setup from there as well:

```js
import 'bootstrap';
import 'elements/turbo-echo-stream-tag';
import 'libs'; // [tl! add]
import 'libs/turbo'; // [tl! remove]
import 'libs/alpine'; // [tl! remove]
// [tl! remove:start]
import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start(); // [tl! remove:end]
```

Now, we can unpin Alpine:

```bash
php artisan importmap:unpin alpinejs
rm resources/js/libs/alpine.js
```

Next, update the `libs/index.js` file:

```js
import 'libs/turbo';
import 'libs/alpine'; // [tl! remove add:-1,1]
import 'controllers';
```

Let's change the `dashboard.blade.php` file to make use of our new `hello_controller.js`:

```blade
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900"> <!-- [tl! remove] -->
                <div class="p-6 text-gray-900" data-controller="hello"> <!-- [tl! add] -->
                    {{ __("You're logged in!") }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
```

You should see the "Hello World!" text instead of "You're logged in!", which means our Stimulus controller is loading!

![Stimulus Controller working](/images/install-stimulus-controller-working.png)

However, our dashboard no longer works. We'll need to create a couple of replacements for the dropdown, the modal, nav, and our quick flash message. Let's get started!

Let's start with the dropdown. We're going to use the [el-transition](https://github.com/mmccall10/el-transition) lib to animate our elements, so let's pin that:

```bash
php artisan importmap:pin el-transition
```

Now, let's generate the Stimulus controller:

```bash
php artisan stimulus:make dropdown_controller
```

Next, replace its contents with the following:

```js
import { Controller } from "@hotwired/stimulus"
import { leave, enter } from "el-transition"

// Connects to data-controller="dropdown"
export default class extends Controller {
    static targets = ['trigger', 'menu']

    static values = {
        open: { type: Boolean, default: false },
    }

    close(event) {
        if (! this.openValue) return;
        if (this.triggerTarget.contains(event.target)) return

        this.openValue = false;
    }

    toggle() {
        this.openValue = ! this.openValue;
    }

    closeNow() {
        this.menuTarget.classList.add('hidden');
    }

    openValueChanged() {
        if (this.openValue) {
            enter(this.menuTarget)
        } else {
            leave(this.menuTarget)
        }
    }
}
```

Update the dropdown Blade component to look like this:

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

<div class="relative" data-controller="dropdown" data-action="turbo:before-cache@window->dropdown#closeNow click@window->dropdown#close close->dropdown#close">
    <div data-action="click->dropdown#toggle" data-dropdown-target="trigger">
        {{ $trigger }}
    </div>

    <div
        data-dropdown-target="menu"
        data-transition-enter="transition ease-out duration-200"
        data-transition-enter-start="transform opacity-0 scale-95"
        data-transition-enter-end="transform opacity-100 scale-100"
        data-transition-leave="transition ease-in duration-75"
        data-transition-leave-start="transform opacity-100 scale-100"
        data-transition-leave-end="transform opacity-0 scale-95"
        class="absolute z-50 mt-2 {{ $width }} rounded-md shadow-lg {{ $alignmentClasses }} hidden"
    >
        <div class="rounded-md ring-1 ring-black ring-opacity-5 {{ $contentClasses }}">
            {{ $content }}
        </div>
    </div>
</div>
```

With that, our dropdowns should be working!

![Dropdowns Working Again](/images/installation-dropdown-controller.png)

Next, let's focus on the flash message. For that, we're going to add a new animation to our `tailwind.config.js` file:

```js
const defaultTheme = require('tailwindcss/defaultTheme');

/** @type {import('tailwindcss').Config} */
module.exports = {
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
            // [tl! add:start]
            animation: {
                'appear-then-fade-out': 'appear-then-fade-out 3s both',
            },

            keyframes: () => ({
                ['appear-then-fade-out']: {
                    '0%, 100%': { opacity: 0 },
                    '10%, 80%': { opacity: 1 },
                },
            }), // [tl! add:end]
        },
    },

    plugins: [require('@tailwindcss/forms')],
};
```

Now, let's generate a new flash Stimulus controller:

```bash
php artisan stimulus:make flash_controller
```

Next, replace it with the following contents:

```js
import { Controller } from "@hotwired/stimulus"

// Connects to data-controller="flash"
export default class extends Controller {
    remove() { // [tl! add:start]
        this.element.remove()
    } // [tl! add:end]
}
```

Then, let's update the `update-password-form.blade.php` Blade view to use both the controller and the new animation. The trick is that we're going to listen to the [animationend CSS event](https://developer.mozilla.org/en-US/docs/Web/API/Element/animationend_event) and once that's done, we're going to remove the element from the DOM. We're also gonna make use of Turbo's [`data-turbo-cache="false"`](https://turbo.hotwired.dev/reference/attributes#data-attributes) to indicate that this element shouldn't be stored in the page cache when we leave the page:

```blade
<section>
    <!-- [tl! collapse:start] -->
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Update Password') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Ensure your account is using a long, random password to stay secure.') }}
        </p>
    </header>
    <!-- [tl! collapse:end] -->
    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-6">
        <!-- [tl! collapse:start] -->
        @csrf
        @method('put')

        <div>
            <x-input-label for="update_current_password" :value="__('Current Password')" />
            <x-text-input id="update_current_password" name="current_password" type="password" class="mt-1 block w-full" autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password" :value="__('New Password')" />
            <x-text-input id="update_password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="update_password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
        </div>
        <!-- [tl! collapse:end] -->
        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600 dark:text-gray-400"
                    data-turbo-cache="false"
                    data-controller="flash"
                    data-action="animationend->flash#remove"
                    class="text-sm text-gray-600 transition animate-appear-then-fade-out"
                >{{ __('Saved.') }}</p> <!-- [tl! remove:-9,5 add:-4,4] -->
            @endif
        </div>
    </form>
</section>
```

Let's also update the `update-profile-information-form.blade.php` file:

```blade
<section>
    <!-- [tl! collapse:start] -->
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>
    <!-- [tl! collapse:end] -->
    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        <!-- [tl! collapse:start] -->
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="email" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>
        <!-- [tl! collapse:end] -->
        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600 dark:text-gray-400"
                    data-turbo-cache="false"
                    data-controller="flash"
                    data-action="animationend->flash#remove"
                    class="text-sm text-gray-600 animate-appear-then-fade-out"
                >{{ __('Saved.') }}</p> <!-- [tl! remove:-9,5 add:-4,4] -->
            @endif
        </div>
    </form>
</section>
```

Now, let's build the TailwindCSS and then test our app:

```bash
php artisan tailwindcss:build
```

You may prefer to keep a watcher running, which you can do by using the `php artisan tailwindcss:watch` command instead of the build one.

Now, the flash messages should appear, then fade away and if you inspect the DOM after they disappear, they should be gone!

![Flash Messages](/images/installation-flash-message.png)

Next, let's fix the modals. Same deal, let's generate the controller:

```bash
php artisan stimulus:make modal_controller
```

Then, replace its contents with the following:

```js
import { Controller } from "@hotwired/stimulus"
import { enter, leave } from "el-transition";

// Connects to data-controller="modal"
export default class extends Controller {
    static targets = ['overlay', 'content'];

    static values = {
        open: Boolean,
        focusable: Boolean,
    }

    static classes = ['overlay']

    open() {
        this.openValue = true;
    }

    close () {
        this.openValue = false;
    }

    hijackFocus(event) {
        if (event.shiftKey) {
            this.focusPrevious()
        } else {
            this.focusNext()
        }
    }

    focusNext() {
        this.nextFocusable.focus()
    }

    focusPrevious() {
        this.prevFocusable.focus()
    }

    closeNow() {
        this.overlayTarget.classList.add('hidden')
        this.contentTarget.classList.add('hidden')
        document.body.classList.remove(this.overlayClass)
        this.openValue = false
    }

    // private

    openValueChanged() {
        if (this.openValue) {
            Promise.all([
                enter(this.element),
                enter(this.overlayTarget),
                enter(this.contentTarget),
            ]).then(() => {
                if (this.focusableValue) {
                    this.firstFocusable.focus()
                    document.body.classList.add(this.overlayClass)
                }
            })
        } else {
            leave(this.element)
            leave(this.contentTarget)
            leave(this.overlayTarget)

            if (this.focusableValue) document.body.classList.remove(this.overlayClass)
        }
    }

    get focusables() {
        let selector = 'a, button, input:not([type=\'hidden\']), textarea, select, details, [tabindex]:not([tabindex=\'-1\'])'

        return [...this.element.querySelectorAll(selector)]
            // All non-disabled elements...
            .filter(el => ! el.hasAttribute('disabled'))
    }

    get firstFocusable() {
        return this.focusables[0]
    }

    get lastFocusable() {
        return this.focusables.slice(-1)[0]
    }

    get nextFocusable() {
        return this.focusables[this.nextFocusableIndex] || this.firstFocusable
    }

    get prevFocusable() {
        return this.focusables[this.prevFocusableIndex] || this.lastFocusable
    }

    get nextFocusableIndex() {
        return this.focusables.indexOf(document.activeElement) + 1 % (this.focusables.length + 1)
    }

    get prevFocusableIndex() {
        return Math.max(0, this.focusables.indexOf(document.activeElement) -1)
    }
}
```

Next, replace the `modal.blade.php` component with this version:

```blade
@props([
    'id',
    'show' => false,
    'maxWidth' => '2xl'
])

@php
$maxWidth = [
    'sm' => 'sm:max-w-sm',
    'md' => 'sm:max-w-md',
    'lg' => 'sm:max-w-lg',
    'xl' => 'sm:max-w-xl',
    '2xl' => 'sm:max-w-2xl',
][$maxWidth];
@endphp

<div
    id="{{ $id }}"
    data-controller="modal"
    data-modal-overlay-class="overflow-y-hidden"
    data-modal-open-value="{{ $show ? 'true' : 'false' }}"
    data-modal-focusable-value="{{ $attributes->has('focusable') ? 'true' : 'false' }}"
    data-action="
        close->modal#close
        keydown.esc@window->modal#close
        keydown.shift+tab->modal#hijackFocus:prevent
        keydown.tab->modal#hijackFocus:prevent
        turbo:before-cache@window->modal#closeNow
    "
    class="{{ $show ? '' : 'hidden' }} fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-50"
>
    <div
        data-action="click->modal#close"
        data-modal-target="overlay"
        class="{{ $show ? '' : 'hidden' }} fixed inset-0 transform transition-all"
        data-transition-enter="ease-out duration-300"
        data-transition-enter-start="opacity-0"
        data-transition-enter-end="opacity-100"
        data-transition-leave="ease-in duration-200"
        data-transition-leave-start="opacity-100"
        data-transition-leave-end="opacity-0"
    >
        <div class="absolute inset-0 bg-gray-500 dark:bg-gray-900 opacity-75"></div>
    </div>

    <div
        class="{{ $show ? '' : 'hidden' }} mb-6 bg-white rounded-lg overflow-hidden shadow-xl transform transition-all sm:w-full {{ $maxWidth }} sm:mx-auto"
        data-modal-target="content"
        data-transition-enter="ease-out duration-300"
        data-transition-enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        data-transition-enter-end="opacity-100 translate-y-0 sm:scale-100"
        data-transition-leave="ease-in duration-200"
        data-transition-leave-start="opacity-100 translate-y-0 sm:scale-100"
        data-transition-leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
    >
        {{ $slot }}
    </div>
</div>
```

The modal will handle a custom event called `open-modal` that may be triggered from anywhere in the page. It expects that event to have the modal name in the event detail. Now, we need a new Stimulus controller that will dispatch that event, since the modal trigger will be outside the modal:

```bash
php artisan stimulus:make modal_trigger_controller
```

Update it with the following contents:

```js
import { Controller } from "@hotwired/stimulus"

// Connects to data-controller="modal-trigger"
export default class extends Controller {
    static outlets = ['modal'];

    open() {
        this.modalOutlet.open();
    }
}
```

Now, let's update the `delete-user-form.blade.php` file to use this controller:

```blade
<section class="space-y-6">
    <!-- [tl! collapse:start] -->
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Delete Account') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
        </p>
    </header>
    <!-- [tl! collapse:end] -->
    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >{{ __('Delete Account') }}</x-danger-button> <!-- [tl! remove:-3,4] -->
    <x-danger-button
        data-controller="modal-trigger"
        data-modal-trigger-modal-outlet="#confirm-user-deletion"
        data-action="click->modal-trigger#open:prevent"
    >{{ __('Delete Account') }}</x-danger-button> <!-- [tl! add:-4,5] -->

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            <!-- [tl! collapse:start] -->
            @csrf
            @method('delete')

            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Are you sure your want to delete your account?</h2>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="Password" class="sr-only" />

                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-3/4"
                    placeholder="Password"
                />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>
            <!-- [tl! collapse:end] -->

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                <x-secondary-button data-action="click->modal#close:prevent"> <!-- [tl! remove:-1,1 add] -->
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-danger-button class="ml-3">
                    {{ __('Delete Account') }}
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
```

Okay, that should get the modal to open if you try to delete the profile (but remember to cancel it):

![Modal Working Again](/images/installation-modal.png)

Now, the only thing remaining that was using Alpine is the navigation. We can use the existing dropdown controller for that, since it behaves the same:

```blade
<nav x-data="{ open: false }" class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
<nav data-controller="dropdown" data-action="turbo:before-cache@window->modal#closeNow" class="bg-white border-b border-gray-100"> <!-- [tl! remove:-1,1 add] -->
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <!-- [tl! collapse:start] -->
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800 dark:text-gray-200" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ml-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ml-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

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
            <!-- [tl! collapse:end] -->
            <!-- Hamburger -->
            <div class="-mr-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-900 focus:text-gray-500 dark:focus:text-gray-400 transition duration-150 ease-in-out">
                <button data-dropdown-target="trigger" data-action="click->dropdown#toggle" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out"> <!-- [tl! remove:-1,1 add] -->
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
    <div data-dropdown-target="menu" class="hidden sm:hidden"> <!-- [tl! remove:-1,1 add] -->
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-600">
            <!-- [tl! collapse:start] -->
            <div class="px-4">
                <div class="font-medium text-base text-gray-800 dark:text-gray-200">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

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
            <!-- [tl! collapse:end] -->
        </div>
    </div>
</nav>
```

Open the DevTools and view the page in responsive mode and test clicking on the hamburber menu, it should open:

![Responsive Nav](/images/installation-nav.png)

Now we're ready for our first feature!

[Continue to creating Chirps...](/creating-chirps)
