# Installation

Our first step is create the app and setup our local enviroment. There are two guides described here, you may choose how you're going to run the app locally as you feel more comfortable.

## Local Installation

If you'd rather have PHP installed locally and using SQLite, this section is for you. This local setup follows the same approach as the Official Laravel Bootcamp. Let's get started.

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

Laravel also has a containerized local development environment called [Laravel Sail](https://laravel.com/docs/sail). Let's assume you don't have PHP or Composer installed locally. To create the project, Laravel provides a build script hosted at [https://laravel.build](https://laravel.build/turbo-chirper) which we can use like this:

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

Before we start working on our features, we'll first need to handle Login and Registration. Luckily for us, Laravel has a set of Starterkits we can use. In this bootcamp, we're using Breeze because of its simplicity. Let's get that installed:

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

And that's it, actually. Get to the Dashboard page, open the DevTools, go to the Console tab, type `Turbo` there and hit enter. You should see that the global Turbo object is there, which means Turbo was successfully installed!

![Turbo Installed](/images/turbo-installed.png)

Turbo is successfully installed!

## Importmap Laravel and TailwindCSS Laravel

To get things more interesting, let's install an alternative frontend setup that doesn't require having Node.js and NPM locally. We could stick with Vite, but I found it's hot code replacement feature not that great when working with Turbo. Feel free to skip this part of the tutorial if you want to keep using Vite.

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

Note: if you're using Sail, remember to prefix this command with `./vendor/bin/sail`, since the symlink needs to be created inside the container.

Next, since we got rid of Vite, we need to install the TailwindCSS Laravel package to handle our CSS compilation:

```bash
composer require tonysm/tailwindcss-laravel
```

That's it. Let's download the TailwindCSS CLI binary and compile the assets the first time:

```bash
php artisan tailwindcss:install
```

This should update our guest and app layouts to add the link tag including the TailwindCSS file using the `tailwindcss()` function provided by the package.

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

Our last piece replacing Alpine for Stimulus. Let's start by installing the [Stimulus Laravel](https://github.com/tonysm/stimulus-laravel) package:

```bash
composer require tonysm/stimulus-laravel
```

Next, let's run the install command:

```bash
php artisan stimulus:install
```

Let's change our main `app.js` file to import the `libs/index.js` file instead of each lib file:

```js
import 'bootstrap';
import 'elements/turbo-echo-stream-tag';
import 'libs/turbo'; // [tl! remove]
import 'libs/alpine'; // [tl! remove]
import 'libs'; // [tl! add]

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();
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

Now, we can unpin Alpine:

```bash
php artisan importmap:unpin alpinejs
rm resources/js/libs/alpine.js
```

Now, remove the imports from our main `app.js` file:

```js
import 'bootstrap';
import 'elements/turbo-echo-stream-tag';
import 'libs';

import Alpine from 'alpinejs'; // [tl! remove:start]

window.Alpine = Alpine;

Alpine.start(); // [tl! remove:end]
```

And from our `libs/index.js` file:

```js
import 'libs/turbo';
import 'libs/alpine'; // [tl! remove]
import 'controllers';
```

Our dashboard no longer works. We'll need to create a couple of replacements for the dropdown, the modal, nav, and our quick flash message. Let's get started!

TODO

Now we're ready for our first feature!

[Continue to creating Chirps...](/creating-chirps)
