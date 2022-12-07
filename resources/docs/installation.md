# Installation

Our first step is to create the web app and setup our local environment.

## Installing Laravel

There are two paths in here: one uses a local installation setup, and another one that uses [Laravel Sail](https://laravel.com/docs/sail). Choose how you're going to run the app locally as you feel more comfortable.

### Quick Installation

If you have already installed PHP and Composer on your local machine, you may create a new Laravel project via [Composer](https://getcomposer.org/):

```bash
composer create-project laravel/laravel turbo-chirper
```

After the project has been created, start Laravel's local development server using the Laravel's Artisan CLI serve command:

```bash
cd turbo-chirper/

php artisan serve
```

Once you have started the Artisan development server, your application will be accessible in your web browser at [http://localhost:8000](http://localhost:8000).

![Laravel Welcome page](/images/welcome-page.png)

For simplicity, you may use SQLite to store your application's data. To instruct Laravel to use SQLite instead of MySQL, update your new application's `.env` file and remove all of the `DB_*` environment variables except for the `DB_CONNECTION` variable, which should be set to `sqlite`:

```env
DB_CONNECTION=sqlite
```

## Installing via Docker

If you do not have PHP installed locally, you may develop your application using [Laravel Sail](https://laravel.com/docs/sail), a light-weight command-line interface for interacting with Laravel's default Docker development environment, which is compatible with all operating systems. Before we get started, make sure to install [Docker](https://docs.docker.com/get-docker/) for your operating system. For alternative installation methods, check out Laravel's full [installation guide](https://laravel.com/docs/installation).

The easiest way to install Laravel is using Laravel's `laravel.build` service, which will download and create a fresh Laravel application for you. Launch a terminal and run the following command:

```bash
curl -s "https://laravel.build/turbo-chirper" | bash
```

Sail installation may take several minutes while Sail's application containers are built on your local machine.

By default, the installer will pre-configure Laravel Sail with a number of useful services for your application, including a MySQL database server. You may [customize the Sail services](https://laravel.com/docs/installation#choosing-your-sail-services) if needed.

After the project has been created, you can navigate to the application directory and start Laravel Sail:

```bash
cd turbo-chirper

./vendor/bin/sail up -d
```

> **Note**
> You can [create a shell alias](https://laravel.com/docs/sail#configuring-a-shell-alias) that allows you execute Sail's commands more easily.

When developing applications using Sail, you may execute Artisan, NPM, and Composer commands via the Sail CLI instead of invoking them directly:

```bash
./vendor/bin/sail php --version
./vendor/bin/sail artisan --version
./vendor/bin/sail composer --version
./vendor/bin/sail npm --version
```

Once the application's Docker containers have been started, you can access the application in your web browser at: [http://localhost](http://localhost).

![Welcome Page over Sail](/images/sail-welcome-page.png)

## Installing Laravel Breeze

Next, we will give your application a head-start by installing [Laravel Breeze](https://laravel.com/docs/starter-kits#laravel-breeze), a minimal, simple implementation of all of Laravel's authentication features, including login, registration, password reset, email verification, and password confirmation. Once installed, you are welcome to customize the components to suit your needs.

Laravel Breeze offers several options for your view layer, including Blade templates, or [Vue](https://vuejs.org/) and [React](https://reactjs.org/) with [Inertia](https://inertiajs.com/). For this tutorial, we'll be using Blade, since it plays nicely with Turbo.

Open a new terminal in your `turbo-chirper` project directory and install your chosen stack with the given commands:

```bash
composer require laravel/breeze --dev

php artisan breeze:install blade
```

Breeze will install and configure your front-end dependencies for you, so we just need to start the Vite development server to automatically recompile our CSS and refresh the browser when we make changes to our Blade templates:

```bash
npm run dev
```

Finally, open another terminal in your `turbo-chirper` project directory and run the initial database migrations to populate the database with the default tables from Laravel and Breeze:

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

## Installing Turbo Laravel

Next, we'll install Turbo Laravel, because this is a Turbo Bootcamp after all!

```bash
composer require tonysm/turbo-laravel

php artisan turbo:install --alpine
```

Since we're using Vite (for now), we need to install the NPM dependencies that were added to our `package.json` file and compile the assets again. If you still have the previous `npm run dev` command running, close it with `CTRL+C` and then run:

```bash
npm install

npm run dev
```

That's it! Get to the Dashboard page, open the DevTools, go to the Console tab, type `Turbo` there and hit enter. You should see that the global Turbo object is there, which means Turbo was successfully installed!

![Turbo Installed](/images/turbo-installed.png)

## Installing Importmap Laravel

To get things more interesting, let's install an alternative frontend setup that doesn't require having Node and NPM locally. We could stick with Vite, but I found it's "Hot Module Replacement" feature not that great when working with Turbo. Feel free to skip this part of the tutorial if you want to keep using Vite.

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

> **Note**
> If you're using Sail, remember to prefix this command with `./vendor/bin/sail`, since the symlink needs to be created inside the container.

## Installing TailwindCSS Laravel

Next, since we replaced Vite with Importmap Laravel, we need to install the TailwindCSS Laravel package to handle our CSS compilation:

```bash
composer require tonysm/tailwindcss-laravel

php artisan tailwindcss:install
```

This should download the TailwindCSS CLI binary, compile the assets for the first time, then update our guest and app layouts that Breeze created to add the link tag including the TailwindCSS file using the `tailwindcss()` function provided by the package.

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

If you refresh the page now, the error should be gone and we're now using Importmap Laravel with TailwindCSS Laravel!

![Error Gone Importmap Welcome](/images/install-error-gone-importmap-welcome.png)

## Installing Stimulus Laravel

Our last piece is replacing Alpine for Stimulus. Let's start by installing the [Stimulus Laravel](https://github.com/tonysm/stimulus-laravel) package:

```bash
composer require tonysm/stimulus-laravel

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

Now we can unpin Alpine:

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

However, our dashboard no longer works as before. We'll need to create some replacements for the dropdown, the modal, nav, and our quick flash message.

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

Let's focus on the flash message next. For that, we're going to add a new animation to our `tailwind.config.js` file:

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
