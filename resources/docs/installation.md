# Installation

This is the demo app for the Turbo Laravel Bootcamp. The first step is to get the app created and our local setup ready. There are two guides described here, you may choose how you're going to run the app locally as you feel more comfortable.

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

For our first set of features, we'll need to handle Login and Registration first. Luckily for us, Laravel has a set of Starterkits we can use. In this bootcamp, we're using Breeze because of its simplicity. Let's get that installed:

```bash
composer require laravel/breeze --dev
php artisan breeze:install
```

We're using the default Blade flavor of Breeze since it pairs nicely with Turbo. Worth saying we're also using Laravel's frontend setup which relies on Vite. Let's compile our assets:

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

TODO

## Stimulus Laravel

TODO

Now we're ready for our first feature!

[Continue to creating Chirps...](/creating-chirps)
