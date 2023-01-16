# *07.* Broadcasting

[TOC]

## Introduction

We can send the same Turbo Streams we're returning to our users after a form submission over WebSockets and update the page for all users visiting it! Broadcasts may be triggered automatically whenever a [model updates](https://laravel.com/docs/eloquent#events) or manually whenever you want to broadcast it.

## Setting Up Soketi

Let's setup [Soketi](https://docs.soketi.app/) to handle our WebSockets connections locally. In production, we can either [deploy Soketi to Forge](https://blog.laravel.com/deploying-soketi-to-laravel-forge) or use a dedicated external service such as [Pusher](https://pusher.com/).

### Quick Installation

For our quick install, we're gonna follow the local CLI installation from [Soketi's docs](https://docs.soketi.app/getting-started/installation/cli-installation).

If you're on Linux, make sure you install these dependencies:

```bash
sudo apt install -y git python3 gcc build-essential
```

Next, install Soketi via NPM:

```bash
npm install -g @soketi/soketi
```

Now, all we have to do is start the Soketi service:

```bash
soketi start
```

This will start the Soketi server at `127.0.0.1:6001`. Your `.env` file should look like this:

```env filename=".env"
# [tl! collapse:start]
APP_NAME=Laravel
APP_ENV=local
APP_KEY=[REDACTED]
APP_DEBUG=true
APP_URL=http://localhost

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=turbo_chirper
DB_USERNAME=sail
DB_PASSWORD=password

BROADCAST_DRIVER=pusher
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
SESSION_DRIVER=file
SESSION_LIFETIME=120

MEMCACHED_HOST=memcached

REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false
# [tl! collapse:end]
PUSHER_APP_ID="app-id"
PUSHER_APP_KEY="app-key"
PUSHER_APP_SECRET="app-secret"
PUSHER_HOST="localhost"
PUSHER_PORT=6001
PUSHER_SCHEME=http
PUSHER_APP_CLUSTER=mt1

PUSHER_FRONTEND_HOST="${PUSHER_HOST}"
PUSHER_FRONTEND_CLUSTER="${PUSHER_APP_CLUSTER}"
```

That's it for setting up Soketi locally.

### Installing via Docker

When using Laravel Sail, we can run Soketi as a Docker Compose service. For that, update your `docker-compose.yml` file to add it:

```yaml filename="docker-compose.yml"
# For more information: https://laravel.com/docs/sail
version: '3'
services:
# [tl! collapse:start]
    laravel.test:
        build:
            context: ./vendor/laravel/sail/runtimes/8.1
            dockerfile: Dockerfile
            args:
                WWWGROUP: '${WWWGROUP}'
        image: sail-8.1/app
        extra_hosts:
            - 'host.docker.internal:host-gateway'
        ports:
            - '${APP_PORT:-80}:80'
        environment:
            WWWUSER: '${WWWUSER}'
            LARAVEL_SAIL: 1
            XDEBUG_MODE: '${SAIL_XDEBUG_MODE:-off}'
            XDEBUG_CONFIG: '${SAIL_XDEBUG_CONFIG:-client_host=host.docker.internal}'
        volumes:
            - '.:/var/www/html'
        networks:
            - sail
        depends_on:
            - mysql
# [tl! collapse:end add:1,13]
    websockets.test:
        image: 'quay.io/soketi/soketi:latest-16-alpine'
        environment:
            SOKETI_DEBUG: '${SOKETI_DEBUG:-1}'
            SOKETI_METRICS_SERVER_PORT: '9601'
            SOKETI_DEFAULT_APP_ID: '${PUSHER_APP_ID}'
            SOKETI_DEFAULT_APP_KEY: '${PUSHER_APP_KEY}'
            SOKETI_DEFAULT_APP_SECRET: '${PUSHER_APP_SECRET}'
        ports:
            - '${PUSHER_FRONTEND_PORT:-6001}:6001'
            - '${PUSHER_METRICS_PORT:-9601}:9601'
        networks:
            - sail
# [tl! collapse:start]
    mysql:
        image: 'mysql/mysql-server:8.0'
        ports:
            - '${FORWARD_DB_PORT:-3306}:3306'
        environment:
            MYSQL_ROOT_PASSWORD: '${DB_PASSWORD}'
            MYSQL_ROOT_HOST: "%"
            MYSQL_DATABASE: '${DB_DATABASE}'
            MYSQL_USER: '${DB_USERNAME}'
            MYSQL_PASSWORD: '${DB_PASSWORD}'
            MYSQL_ALLOW_EMPTY_PASSWORD: 1
        volumes:
            - 'sail-mysql:/var/lib/mysql'
            - './vendor/laravel/sail/database/mysql/create-testing-database.sh:/docker-entrypoint-initdb.d/10-create-testing-database.sh'
        networks:
            - sail
        healthcheck:
            test: ["CMD", "mysqladmin", "ping", "-p${DB_PASSWORD}"]
            retries: 3
            timeout: 5s
networks:
    sail:
        driver: bridge
volumes:
    sail-mysql:
        driver: local
# [tl! collapse:end]
```

Before booting the new service, make sure your `.env` file looks like this:

```env filename=".env"
# [tl! collapse:start]
APP_NAME=Laravel
APP_ENV=local
APP_KEY=[REDACTED]
APP_DEBUG=true
APP_URL=http://localhost

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=turbo_chirper
DB_USERNAME=sail
DB_PASSWORD=password

BROADCAST_DRIVER=pusher
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
SESSION_DRIVER=file
SESSION_LIFETIME=120

MEMCACHED_HOST=memcached

REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false
# [tl! collapse:end]
PUSHER_APP_ID="app-id"
PUSHER_APP_KEY="app-key"
PUSHER_APP_SECRET="app-secret"
PUSHER_HOST="websockets.test"
PUSHER_PORT=6001
PUSHER_SCHEME=http
PUSHER_APP_CLUSTER=mt1

PUSHER_FRONTEND_HOST="localhost"
PUSHER_FRONTEND_CLUSTER="${PUSHER_APP_CLUSTER}"
```

Since containers run in isolation, we'll need two different hosts. Our backend will connect using the Docker Compose service name as the host, since Docker Compose will ensure both containers are running in the same network. That's why we're setting `PUSHER_HOST` to `websockets.test`.

However, our browser also needs to connect to the Soketi service. We're binding the Soketi container to our local port `6001`, so our browser can connect o `localhost:6001`. That's why we're setting `PUSHER_FRONTEND_HOST` to `localhost`.

Now, we can boot the Soketi service by running:

```bash
./vendor/bin/sail up -d
```

That's it!

## Setting Up The Broadcasting Component

We're gonna split this part into two parts: the backend and the frontend.

### The Backend

Install the Composer dependencies:

```bash
composer require pusher/pusher-php-server
```

Now, update the `config/broadcasting.php`:

```php filename="config/broadcasting.php"
<?php

return [
    // [tl! collapse:start]
    /*
    |--------------------------------------------------------------------------
    | Default Broadcaster
    |--------------------------------------------------------------------------
    |
    | This option controls the default broadcaster that will be used by the
    | framework when an event needs to be broadcast. You may set this to
    | any of the connections defined in the "connections" array below.
    |
    | Supported: "pusher", "ably", "redis", "log", "null"
    |
    */
    // [tl! collapse:end]
    'default' => env('BROADCAST_DRIVER', 'null'),
    // [tl! collapse:start]
    /*
    |--------------------------------------------------------------------------
    | Broadcast Connections
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the broadcast connections that will be used
    | to broadcast events to other systems or over websockets. Samples of
    | each available type of connection are provided inside this array.
    |
    */
    // [tl! collapse:end]
    'connections' => [

        'pusher' => [
            'driver' => 'pusher',
            'key' => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'app_id' => env('PUSHER_APP_ID'),
            'options' => [
                'host' => env('PUSHER_HOST') ?: 'api-'.env('PUSHER_APP_CLUSTER', 'mt1').'.pusher.com',
                'port' => env('PUSHER_PORT', 443),
                'scheme' => env('PUSHER_SCHEME', 'https'),
                'encrypted' => false,
                'useTLS' => env('PUSHER_SCHEME', 'https') === 'https',
            ],
            'client_options' => [
                // Guzzle client options: https://docs.guzzlephp.org/en/stable/request-options.html
            ],
            'frontend_options' => [ // [tl! add:start]
                'host' => env('PUSHER_FRONTEND_HOST', env('PUSHER_HOST') ?: 'api-'.env('PUSHER_APP_CLUSTER', 'mt1').'.pusher.com'),
                'port' => env('PUSHER_FRONTEND_PORT', env('PUSHER_PORT', 443)),
                'cluster' => env('PUSHER_APP_CLUSTER', 'mt1'),
                'forceTLS' => env('PUSHER_SCHEME', 'https') === 'https',
            ], // [tl! add:end]
        ],
        // [tl! collapse:start]
        'ably' => [
            'driver' => 'ably',
            'key' => env('ABLY_KEY'),
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
        ],

        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],
        // [tl! collapse:end]
    ],

];
```

Then, uncommend the `BroadcastsServiceProvider` from the list of providers in `config/app.php`:

```php filename="config/app.php"
<?php

use Illuminate\Support\Facades\Facade;

return [
    // [tl! collapse:start]
    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | your application so that it is used when running Artisan tasks.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    'asset_url' => env('ASSET_URL'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. We have gone
    | ahead and set this to a sensible default for you out of the box.
    |
    */

    'timezone' => 'UTC',

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by the translation service provider. You are free to set this value
    | to any of the locales which will be supported by the application.
    |
    */

    'locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Application Fallback Locale
    |--------------------------------------------------------------------------
    |
    | The fallback locale determines the locale to use when the current one
    | is not available. You may change the value to correspond to any of
    | the language folders that are provided through your application.
    |
    */

    'fallback_locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Faker Locale
    |--------------------------------------------------------------------------
    |
    | This locale will be used by the Faker PHP library when generating fake
    | data for your database seeds. For example, this will be used to get
    | localized telephone numbers, street address information and more.
    |
    */

    'faker_locale' => 'en_US',

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the Illuminate encrypter service and should be set
    | to a random, 32 character string, otherwise these encrypted strings
    | will not be safe. Please do this before deploying an application!
    |
    */

    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => 'file',
        // 'store'  => 'redis',
    ],

    /*
    |--------------------------------------------------------------------------
    | Autoloaded Service Providers
    |--------------------------------------------------------------------------
    |
    | The service providers listed here will be automatically loaded on the
    | request to your application. Feel free to add your own services to
    | this array to grant expanded functionality to your applications.
    |
    */
    // [tl! collapse:end]
    'providers' => [
        // [tl! collapse:start]
        /*
         * Laravel Framework Service Providers...
         */
        Illuminate\Auth\AuthServiceProvider::class,
        Illuminate\Broadcasting\BroadcastServiceProvider::class,
        Illuminate\Bus\BusServiceProvider::class,
        Illuminate\Cache\CacheServiceProvider::class,
        Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
        Illuminate\Cookie\CookieServiceProvider::class,
        Illuminate\Database\DatabaseServiceProvider::class,
        Illuminate\Encryption\EncryptionServiceProvider::class,
        Illuminate\Filesystem\FilesystemServiceProvider::class,
        Illuminate\Foundation\Providers\FoundationServiceProvider::class,
        Illuminate\Hashing\HashServiceProvider::class,
        Illuminate\Mail\MailServiceProvider::class,
        Illuminate\Notifications\NotificationServiceProvider::class,
        Illuminate\Pagination\PaginationServiceProvider::class,
        Illuminate\Pipeline\PipelineServiceProvider::class,
        Illuminate\Queue\QueueServiceProvider::class,
        Illuminate\Redis\RedisServiceProvider::class,
        Illuminate\Auth\Passwords\PasswordResetServiceProvider::class,
        Illuminate\Session\SessionServiceProvider::class,
        Illuminate\Translation\TranslationServiceProvider::class,
        Illuminate\Validation\ValidationServiceProvider::class,
        Illuminate\View\ViewServiceProvider::class,

        /*
         * Package Service Providers...
         */
        // [tl! collapse:end]
        /*
         * Application Service Providers...
         */
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        // App\Providers\BroadcastServiceProvider::class,
        App\Providers\BroadcastServiceProvider::class, // [tl! remove:-1,1 add]
        App\Providers\EventServiceProvider::class,
        App\Providers\RouteServiceProvider::class,

    ],
    // [tl! collapse:start]
    /*
    |--------------------------------------------------------------------------
    | Class Aliases
    |--------------------------------------------------------------------------
    |
    | This array of class aliases will be registered when this application
    | is started. However, feel free to register as many as you wish as
    | the aliases are "lazy" loaded so they don't hinder performance.
    |
    */

    'aliases' => Facade::defaultAliases()->merge([
        // 'ExampleClass' => App\Example\ExampleClass::class,
    ])->toArray(),
    // [tl! collapse:end]
];
```

Now, since we're using Importmap Laravel, we need to expose the JS Pusher keys to our frontend somehow (in a Vite setup we could reach for them using `import.meta.VITE_*`, but we don't have a build compilation step here.)

For that reason, we're gonna add some meta tags to our `app.blade.php` and `guest.blade.php` layouts that will expose those configs for our JS frontend:

```blade filename="resources/views/layouts/app.blade.php"
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <!-- [tl! add:1,1] -->
        @include('layouts.current-meta')

        <title>{{ config('app.name', 'Laravel') }}</title>
        <!-- [tl! collapse:start] -->
        <!-- Fonts -->
        <link rel="stylesheet" href="https://fonts.bunny.net/css2?family=Nunito:wght@400;600;700&display=swap">

        <!-- Scripts -->
        <x-importmap-tags />
        <link rel="stylesheet" href="{{ tailwindcss('css/app.css') }}">
        <!-- [tl! collapse:end] -->
    </head>
    <body class="font-sans antialiased">
        <!-- [tl! collapse:start] -->
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')
            @include('layouts.notifications')

            <!-- Page Heading -->
            @if (isset($header))
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>
        <!-- [tl! collapse:end] -->
    </body>
</html>
```

And also update the `guest` layout:

```blade filename="resources/views/layouts/guest.blade.php"
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <!-- [tl! add:1,1] -->
        @include('layouts.current-meta')

        <title>{{ config('app.name', 'Laravel') }}</title>
        <!-- [tl! collapse:start] -->
        <!-- Fonts -->
        <link rel="stylesheet" href="https://fonts.bunny.net/css2?family=Nunito:wght@400;600;700&display=swap">

        <!-- Scripts -->
        <x-importmap-tags />
        <link rel="stylesheet" href="{{ tailwindcss('css/app.css') }}">
        <!-- [tl! collapse:end] -->
    </head>
    <body>
        <!-- [tl! collapse:start] -->
        <div class="font-sans text-gray-900 antialiased">
            {{ $slot }}
        </div>
        <!-- [tl! collapse:end] -->
    </body>
</html>
```

Let's create the `layouts/current-meta.blade.php` partial:

```blade filename="resources/views/layouts/current-meta.blade.php"
{{-- Pusher Client-Side Config --}}
<meta name="current-pusher-key" content="{{ config('broadcasting.connections.pusher.key') }}" />
<meta name="current-pusher-cluster" content="{{ config('broadcasting.connections.pusher.frontend_options.cluster') }}" />
<meta name="current-pusher-wsHost" content="{{ config('broadcasting.connections.pusher.frontend_options.host') }}" />
<meta name="current-pusher-wsPort" content="{{ config('broadcasting.connections.pusher.frontend_options.port') }}" />
<meta name="current-pusher-forceTLS" content="{{ json_encode(boolval(config('broadcasting.connections.pusher.frontend_options.forceTLS'))) }}" />
```

Note that all our meta tags are exposed using the `current-pusher-*` prefix. That's gonna be important.

### The Frontend

Before we set up Laravel Echo, let's install the JS dependencies:

```bash
php artisan importmap:pin laravel-echo pusher-js
```

Now, let's configure Laravel Echo. Uncomment the Laravel Echo settings in our `bootstrap.js`:

```js filename="resources/js/bootstrap.js"
// [tl! collapse:start]
import _ from 'lodash';
window._ = _;

/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */
// [tl! collapse:end]
import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
    wsHost: import.meta.env.VITE_PUSHER_HOST ? import.meta.env.VITE_PUSHER_HOST : `ws-${import.meta.env.VITE_PUSHER_APP_CLUSTER}.pusher.com`,
    wsPort: import.meta.env.VITE_PUSHER_PORT ?? 80,
    wssPort: import.meta.env.VITE_PUSHER_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});
```

This file was written assuming we were using Vite, but we're not. Since we're using Importmap Laravel, we don't have access to a build step, which is how Vite replaces these `import.meta.env.*` with the values from your `.env` file.

Remember that we're exposing some meta tags in our HTML document head, so we're going to configure Laravel Echo using those meta tags.

We could reach for them individually using something like:

```js
document.head.querySelector('meta[name=current-pusher-key]').content
```

But we can actually use a trick that the 37signals folks are using on Hey. We can define a JS Proxy that will give us an object interface we can use to read meta data from our HTML document.

First, let's create a new lib called `current.js` that will look like this:

```js filename="resources/js/libs/current.js"
// On-demand JavaScript objects from "current" HTML <meta> elements. Example:
//
// <meta name="current-identity-id" content="123">
// <meta name="current-identity-time-zone-name" content="Central Time (US & Canada)">
//
// >> current.identity
// => { id: "123", timeZoneName: "Central Time (US & Canada)" }
//
// >> current.foo
// => {}
export const current = new Proxy({}, {
  get(target, propertyName) {
    const result = {}
    const prefix = `current-${propertyName}-`
    for (const { name, content } of document.head.querySelectorAll(`meta[name^=${prefix}]`)) {
      const key = camelize(name.slice(prefix.length))
      result[key] = content
    }
    return result
  }
})

function camelize(string) {
  return string.replace(/(?:[_-])([a-z0-9])/g, (_, char) => char.toUpperCase())
}
```

This snippet was taken from the Hey frontend source code, which is fully available to anyone to learn from in the page sources. Based on the comments, we can see how we can use it. In our case, we can access all of our `current-pusher-*` configs as an object by reaching for `current.pusher`, which would give us an object like so:

```js
{
    key: "app-key",
    cluster: "mt1",
    wsHost: "localhost",
    wsPort: 6001,
    forceTLS: "false",
}
```

Now, we can import that current object in our `bootstrap.js` file and replace all the `import.meta.env.*` calls with the following:

```js filename="resources/js/bootstrap.js"
// [tl! collapse:start]
import _ from 'lodash';
window._ = _;

/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
// [tl! collapse:end add:1,2]
import { current } from 'libs/current';
window.current = current;
// [tl! collapse:start]
/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */
// [tl! collapse:end]
import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY, // [tl! remove:0,6]
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
    wsHost: import.meta.env.VITE_PUSHER_HOST ? import.meta.env.VITE_PUSHER_HOST : `ws-${import.meta.env.VITE_PUSHER_APP_CLUSTER}.pusher.com`,
    wsPort: import.meta.env.VITE_PUSHER_PORT ?? 80,
    wssPort: import.meta.env.VITE_PUSHER_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
    key: current.pusher.key, // [tl! add:0,6]
    cluster: current.pusher.cluster,
    wsHost: current.pusher.wsHost,
    wsPort: current.pusher.wsPort ?? 80,
    wssPort: current.pusher.wssPort ?? 443,
    forceTLS: (current.pusher.forceTLS ?? 'false') == true,
    enabledTransports: ['ws', 'wss'],
});
```

Now we're set!

## Broadcasting Turbo Streams

Let's start by sending new Chirps to all users currently visiting the chirps page. We're going to start by creating a private broadcasting channel called `chirps` in our `routes/channels.php` file. Any authenticated user may start receiving new Chirps broadcasts when they visit the `chirps.index` page, so we're simply returning `true` in the authorization check:

```php filename="routes/channels.php"
<?php

use App\Models\Chirp;
use Illuminate\Support\Facades\Broadcast;
// [tl! collapse:start]
/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/
// [tl! collapse:end]
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
// [tl! add:1,3]
Broadcast::channel('chirps', function () {
    return true;
});
```

Now, let's update the `chirps/index.blade.php` to add the `x-turbo-stream-from` Blade component that ships with Turbo Laravel:

```blade filename="resources/views/chirps/index.blade.php"
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Chirps') }}
        </h2>
    </x-slot>
    <!-- [tl! add:1,1] -->
    <x-turbo-stream-from source="chirps" />

    <div class="max-w-2xl mx-auto p-4 sm:p-6 lg:p-8">
        <!-- [tl! collapse:start] -->
        <x-turbo-frame id="create_chirp" src="{{ route('chirps.create') }}">
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
        <!-- [tl! collapse:end] -->
    </div>
</x-app-layout>
```

That's it! When the user visits that page, this component will automatically start listening to a `chirps` _private_ channel for broadcasts. By default, it assumes we're using private channels, but you may configure it to listen to `presence` or `public` channels by passing the `type` prop to the component. In this case, we're passing a string for the channel name, but we could also pass an Eloquent model instance and it would figure out the channel name based on [Laravel's conventions](https://laravel.com/docs/broadcasting#model-broadcasting-conventions).

Now, we're ready to start broadcasting! First, let's add the `Broadcasts` trait to our `Chirp` model:

```php filename="app/Models/Chirp.php"
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Tonysm\TurboLaravel\Models\Broadcasts; // [tl! add]

class Chirp extends Model
{
    use HasFactory;
    use Broadcasts; // [tl! add]

    protected $fillable = [
        'message',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

That trait will give us a bunch of methods we can call from our Chirp model instances. Let's use it in the `store` action of our `ChirpController` to send newly created Chirps to all connected users:

```php filename="app/Http/Controllers/ChirpController.php"
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
            'chirps' => Chirp::with('user')->latest()->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('chirps.create', [
            //
        ]);
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
        // [tl! add:1,6]
        $chirp->broadcastPrependTo('chirps')
            ->target('chirps')
            ->partial('chirps._chirps', [
                'chirp' => $chirp,
            ])
            ->toOthers();

        if ($request->wantsTurboStream()) {
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
     * @param  \App\Models\Chirp  $chirp
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

To test this, try visiting the `/chirps` page from two different tabs and creating a Chirp in one of them. The other should automatically update! We're also broadcasting on-the-fly in the same request/response life-cycle, which could slow down our response time a bit, depending on your load and your queue driver response time. We can delay the broadcasting (which includes view rendering) to the a queued job by chaining the `->later()` method, for example.

Now, let's make sure all visiting users receive Chirp updates whenever it changes. To achieve that, change the `update` action in the `ChirpController`:

```php filename="app/Http/Controllers/ChirpController.php"
<?php
// [tl! collapse:start]
namespace App\Http\Controllers;

use App\Models\Chirp;
use Illuminate\Http\Request;
// [tl! collapse:end add:1,1]
use function Tonysm\TurboLaravel\dom_id;

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
            'chirps' => Chirp::with('user')->latest()->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('chirps.create', [
            //
        ]);
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

        $chirp->broadcastPrependTo('chirps')
            ->target('chirps')
            ->partial('chirps._chirps', [
                'chirp' => $chirp,
            ])
            ->toOthers();

        if ($request->wantsTurboStream()) {
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
        // [tl! add:1,6]
        $chirp->broadcastReplaceTo('chirps')
            ->target(dom_id($chirp))
            ->partial('chirps._chirps', [
                'chirp' => $chirp,
            ])
            ->toOthers();

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
    // [tl! collapse:start]
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Chirp  $chirp
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

Again, open two tabs, try editing a Chirp and you should see the other tab automatically updating! Cool, right?!

Finally, let's make sure deleted Chirps are removed from all visiting users' pages. Tweak the `destroy` action in the `ChirpController` like so:

```php filename="app/Http/Controllers/ChirpController.php"
<?php
// [tl! collapse:start]
namespace App\Http\Controllers;

use App\Models\Chirp;
use Illuminate\Http\Request;

use function Tonysm\TurboLaravel\dom_id;
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
            'chirps' => Chirp::with('user')->latest()->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('chirps.create', [
            //
        ]);
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

        $chirp->broadcastPrependTo('chirps')
            ->target('chirps')
            ->partial('chirps._chirps', [
                'chirp' => $chirp,
            ])
            ->toOthers();

        if ($request->wantsTurboStream()) {
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
    public function update(Request $request, Chirp $chirp)
    {
        $this->authorize('update', $chirp);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:255'],
        ]);

        $chirp->update($validated);

        $chirp->broadcastReplaceTo('chirps')
            ->target(dom_id($chirp))
            ->partial('chirps._chirps', [
                'chirp' => $chirp,
            ])
            ->toOthers();

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
    // [tl! collapse:end]
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Chirp  $chirp
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Chirp $chirp)
    {
        $this->authorize('delete', $chirp);

        $chirp->delete();
        // [tl! add:1,3]
        $chirp->broadcastRemoveTo('chirps')
            ->target(dom_id($chirp))
            ->toOthers();

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
}
```

Now, open two tabs and try deleting a Chirp. You should see it being removed from the other tab as well!

## Automatically Broadcasting on Model Changes

Since we're interested in broadcasting all changes of our Chirp model, we can remove a few lines of code and instruct Turbo Laravel to make that automatically for us.

We may achieve that by setting the `$broadcasts` property to `true` in our `Chirp` model. However, Turbo Laravel will automatically broadcast newly created models using the `append` Turbo Stream action. In our case, we want it to `prepend` instead, so we're setting the `$broadcasts` property to an array and using the `insertsBy` key to configure the creation action to be used.

We also need to override where these broadcasts are going to be sent to. Turbo Laravel will automatically send creates to a channel named using the pluralization of our model's basename, which would work for us. But updates and deletes will be sent to a model's individual channel names (something like `App.Models.Chirp.1` where `1` is the model ID). This is useful because we're usually broadcasting to a parent model's channel via a relationship, which we can do with the `$broadcastsTo` property (see [the docs](https://turbo-laravel.com/docs/1.x/broadcasting#content-broadcasting-model-changes) to know more about this), but in our case we'll always be sending the broadcasts to a private channel named `chirps`.

Our `Chirp` model would end up looking like this:

```php filename="app/Models/Chirp.php"
<?php

namespace App\Models;

use Illuminate\Broadcasting\PrivateChannel; // [tl! add]
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Tonysm\TurboLaravel\Models\Broadcasts;

class Chirp extends Model
{
    use HasFactory;
    use Broadcasts;
    // [tl! add:1,3]
    protected $broadcasts = [
        'insertsBy' => 'prepend',
    ];

    protected $fillable = [
        'message',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    // [tl! add:1,6]
    public function broadcastsTo()
    {
        return [
            new PrivateChannel('chirps'),
        ];
    }
}
```

We can then remove a few lines from our `ChirpsController`:

```php filename="app/Http/Controllers/ChirpController.php"
<?php
// [tl! collapse:start]
namespace App\Http\Controllers;

use App\Models\Chirp;
use Illuminate\Http\Request;
// [tl! collapse:end remove:1,1]
use function Tonysm\TurboLaravel\dom_id;

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
            'chirps' => Chirp::with('user')->latest()->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('chirps.create', [
            //
        ]);
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
        // [tl! remove:1,6]
        $chirp->broadcastPrependTo('chirps')
            ->target('chirps')
            ->partial('chirps._chirps', [
                'chirp' => $chirp,
            ])
            ->toOthers();

        if ($request->wantsTurboStream()) {
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
        // [tl! remove:1,6]
        $chirp->broadcastReplaceTo('chirps')
            ->target(dom_id($chirp))
            ->partial('chirps._chirps', [
                'chirp' => $chirp,
            ])
            ->toOthers();

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
     * @param  \App\Models\Chirp  $chirp
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Chirp $chirp)
    {
        $this->authorize('delete', $chirp);

        $chirp->delete();
        // [tl! remove:1,3]
        $chirp->broadcastRemoveTo('chirps')
            ->target(dom_id($chirp))
            ->toOthers();

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
}
```

> **note**
> We're only covering Turbo Stream broadcasts from an Eloquent model's perspective. However, you may broadcast anything using the `TurboStream` Facade or by chaining the `broadcastTo()` method call when using the `turbo_stream()` response builder function. Check the [Broadcasting docs](https://turbo-laravel.com/docs/1.x/broadcasting#content-handmade-broadcasts) to know more about this.

## Testing it out

One more cool thing about this approach: users will receive the broadcasts no matter where the Chirp models were created from! We can test this out by creating a Chirp entry from Tinker, for example. To try that, start a new Tinker session:

```bash
php artisan tinker
```

And then create a Chirp from there:

```php
User::first()->chirps()->create(['message' => 'Hello from Tinker!!'])
# App\Models\Chirp {#4804
#   message: "Hello from Tinker!!",
#   user_id: 1,
#   updated_at: "2023-01-16 19:46:28",
#   created_at: "2023-01-16 19:46:28",
#   id: 13,
# }
```

![Broadcasting from Tinker](/images/broadcasting-tinker.png)

### Extra Credit: Fixing The Missing Dropdowns

If we were using a real async queue driver and sending broadcasting to the queue, we'd notice the dropdowns gone missing from our Turbo Stream broadcasts! Refreshing the page would make them appear again. That's because when we send the broadcasts to run in background our partial will render without a session context, so our calls to `Auth::id()` inside of it will always return `null`, which means the dropdown would never render.

Instead of conditionally rendering the dropdown in the server side, we're always going to render it. Then, we're going to hide it from our users with a sprinkle of JavaScript.

First, let's update our `layouts.current-meta.blade.php` partial to include a few things about the currently authenticated user when there's one:

```blade filename="resources/views/layouts/current-meta.blade.php"
{{-- Pusher Client-Side Config --}}
<meta name="current-pusher-key" content="{{ config('broadcasting.connections.pusher.key') }}" />
<meta name="current-pusher-cluster" content="{{ config('broadcasting.connections.pusher.frontend_options.cluster') }}" />
<meta name="current-pusher-wsHost" content="{{ config('broadcasting.connections.pusher.frontend_options.host') }}" />
<meta name="current-pusher-wsPort" content="{{ config('broadcasting.connections.pusher.frontend_options.port') }}" />
<meta name="current-pusher-forceTLS" content="{{ config('broadcasting.connections.pusher.frontend_options.forceTLS') ? 'true' : 'false' }}" />
<!-- [tl! add:1,4] -->
@auth
<meta name="current-identity-id" content="{{ Auth::user()->id }}" />
<meta name="current-identity-name" content="{{ Auth::user()->name }}" />
@endauth
```

Now, we're going to create a new Stimulus controller that is going to be responsible of this dropdown visibilily. It should only show it if the currently authenticated user was the creator of the Chirp. First, let's create the controller:

```bash
php artisan stimulus:make visible_to_creator
```

Now, update the Stimulus controller to look like this:

```js filename="resources/js/controllers/visible_to_creator_controller.js"
import { Controller } from "@hotwired/stimulus"
import { current } from 'libs/current'

// Connects to data-controller="visible-to-creator"
export default class extends Controller {
    static values = {
        'id': String,
    };

    static classes = ['hidden'];

    connect() {
        this.toggleVisibility();
    }

    toggleVisibility() {
        if (this.idValue == current.identity.id) {
            this.element.classList.remove(...this.hiddenClasses);
        } else {
            this.element.classList.add(...this.hiddenClasses);
        }
    }
}
```

Now, let's update our `_chirp.blade.php` partial to use this controller instead of handling this in the server-side:

```blade filename="resources/views/chirps/_chirp.blade.php"
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
                    <small class="ml-2 text-sm text-gray-600">
                        <x-relative-time :date="$chirp->created_at" />
                    </small>
                    @unless ($chirp->created_at->eq($chirp->updated_at))
                    <small class="text-sm text-gray-600"> &middot; edited</small>
                    @endunless
                </div>
                <!-- [tl! collapse:end remove:1,2 add:3,8] -->
                @if (Auth::id() === $chirp->user->id)
                <x-dropdown align="right" width="48">
                <x-dropdown
                    align="right"
                    width="48"
                    class="hidden"
                    data-controller="visible-to-creator"
                    data-visible-to-creator-id-value="{{ $chirp->user_id }}"
                    data-visible-to-creator-hidden-class="hidden"
                >
                    <!-- [tl! collapse:start] -->
                    <x-slot name="trigger">
                        <button>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <a href="{{ route('chirps.edit', $chirp) }}" class="block w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:bg-gray-100 transition duration-150 ease-in-out">
                            Edit
                        </a>

                        <form action="{{ route('chirps.destroy', $chirp) }}" method="POST">
                            @method('DELETE')

                            <button class="block w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:bg-gray-100 transition duration-150 ease-in-out">
                                Delete
                            </button>
                        </form>
                    </x-slot>
                    <!-- [tl! collapse:end] -->
                </x-dropdown> <!-- [tl! remove:1,1] -->
                @endif
            </div>
            <p class="mt-4 text-lg text-gray-900">{{ $chirp->message }}</p>
        </div>
    </div>
</x-turbo-frame>
```

Next, we need to tweak our `dropdown.blade.php` Blade component to accept and merge the `class`, `data-controller`, and `data-action` attributes:

```blade filename="resources/views/components/dropdown.blade.php"
@props(['align' => 'right', 'width' => '48', 'contentClasses' => 'py-1 bg-white'])
@props(['align' => 'right', 'width' => '48', 'class' => '', 'contentClasses' => 'py-1 bg-white', 'dataController' => '', 'dataAction' => ''])
<!-- [tl! remove:-2,1 add:-1,1 collapse:start] -->
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
<!-- [tl! collapse:end remove:1,1 add:2,1] -->
<div class="relative" data-controller="dropdown" data-action="turbo:before-cache@window->dropdown#closeNow click@window->dropdown#close close->dropdown#close">
<div class="relative {{ $class }}" data-controller="dropdown {{ $dataController }}" data-action="turbo:before-cache@window->dropdown#closeNow click@window->dropdown#close close->dropdown#close {{ $dataAction }}" {{ $attributes }}>
    <!-- [tl! collapse:start] -->
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
    <!-- [tl! collapse:end] -->
</div>
```

Now, if you try creating another user and test this out, you'll see that the dropdown only shows up for the creator of the Chirp!

![Dropdown only shows up for creator](/images/broadcasting-dropdown-fix.png)

This change also makes our entire `_chirp` partial cacheable! We could cache it and only render that when changes are made to the Chirp model using the Chirp's `updated_at` timestamps, for example.

> **warning**
> Hiding the links in the frontend _**MUST NOT**_ be your only protection here. Always ensure users are authorized to perform actions in the server side. We're already doing this in our controller using [Laravel's Authorization Policies](https://laravel.com/docs/authorization#introduction).

[Continue to setting up the native app...](/native-setup)
