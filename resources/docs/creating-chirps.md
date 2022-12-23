# Creating Chirps

[TOC]

## Introduction

Let's allow users to post short messages called _Chirps_.

## Models, migrations, and controllers

To allow users to post _Chirps_, we'll use migrations, models, and controllers. Let's briefly cover those concepts:

* [Models](https://laravel.com/docs/eloquent) provide a powerful and enjoyable interface for you to interact with the tables in your database.
* [Migrations](https://laravel.com/docs/migrations) allow you to easily create and modify the tables in your database. They ensure that the same database structure exists everywhere that your application runs.
* [Controllers](https://laravel.com/docs/controllers) are responsible for processing requests made to your application and returning a response.

Almost every feature you build will involve all of these pieces working together in harmony, so the `artisan make:model` command can create them all for you at once.

Let's create a model, migration, and resource controller for our Chirps with the following command:

```bash
php artisan make:model -mcr Chirp
```

You can see all the available options by using the `--help` option, like `php artisan make:model --help`.

This command will create three files:

* `app/Models/Chirp.php` - The Eloquent model.
* `database/migrations/<timestamp>_create_chirps_table.php` - The database migration that will create the database table.
* `app/Http/Controller/ChirpController.php` - The HTTP controller that will take incoming requests and return responses.

## Routing

We will also need to create URLs for our controller. We can do this by adding "routes", which are managed in the `routes` directory of your project. Because we're using a resource controller, we can use a single `Route::resource()` statement to define all of the routes following a conventional URL structure.

To start with, we are going to enable three routes:

* The `index` route will display our listing of Chirps.
* The `create` route will display the form to create Chirps.
* The `store` route will be used for saving new Chirps.

We are also going to place these routes behind two [middlewares](https://laravel.com/docs/middleware):

* The `auth` middleware ensures that only logged-in users can access the route.
* The `verified` middleware will be used if you decide to enable [email verification](https://laravel.com/docs/verification).

```php
<?php

use App\Http\Controllers\ChirpController; // [tl! add]
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
// [tl! collapse:start]
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});
// [tl! collapse:end]
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::resource('chirps', ChirpController::class) // [tl! add:start]
    ->only(['index', 'create', 'store'])
    ->middleware(['auth', 'verified']); // [tl! add:end]

require __DIR__.'/auth.php';
```

This will create the following routes:

| Verb | URI | Action | Route Name |
|---|---|---|---|
| GET | `/chirps` | index | `chirps.index` |
| GET | `/chirps/create` | create | `chirps.create` |
| POST | `/chirps` | store | `chirps.store` |

You may view all of the routes for your application by running the `php artisan route:list` command.

Let's test our route and controller by returning a test message from the `index` method of our new `ChirpController` class:

```php
<?php
// [tl! collapse:start]
namespace App\Http\Controllers;

use App\Models\Chirp;
use Illuminate\Http\Request;
// [tl! collapse:end]
class ChirpController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        return 'Hello, World!'; // [tl! remove:-1,1 add]
    }
    // [tl! collapse:start]
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
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
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Chirp  $chirp
     * @return \Illuminate\Http\Response
     */
    public function destroy(Chirp $chirp)
    {
        //
    }
    // [tl! collapse:end]
}
```

If you are still logged in from earlier, you should see your message when navigating to [http://localhost:8000/chirps](http://localhost:8000/chirps), or [http://localhost/chirps](http://localhost/chirps) if you're using Sail!

### Adding The Form

Let's update our `index` action in the `ChirpController` to render the view that will display the listing of Chirps and a link to create a Chirp. We'll also update the `create` action to render the view that will display the form to create Chirps:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Chirp;
use Illuminate\Http\Request;

class ChirpController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return 'Hello, World!';
        return view('chirps.index', [ // [tl! remove:-1,1 add:start]
            //
        ]);// [tl! add:end]
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
        return view('chirps.create', [ // [tl! remove:-1,1 add:start]
            //
        ]); // [tl! add:end]
    }
    // [tl! collapse:start]
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
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
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Chirp  $chirp
     * @return \Illuminate\Http\Response
     */
    public function destroy(Chirp $chirp)
    {
        //
    }
    // [tl! collapse:end]
}
```

We can then create our `chirps.index` view with a link to our form for creating new Chirps:

```blade filename=resources/views/chirps/index.blade.php
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Chirps') }}
        </h2>
    </x-slot>

    <div class="max-w-2xl mx-auto p-4 sm:p-6 lg:p-8">
        <div class="relative flex items-center justify-center py-10 px-4 rounded-lg border border-dotted border-gray-300">
            <a class="text-gray-700" href="{{ route('chirps.create') }}">
                Add a new Chirp
                <span class="absolute inset-0"></span>
            </a>
        </div>
    </div>
</x-app-layout>
```

Then, let's create our `chirps.create` page view with the Chirps form:

```blade filename=resources/views/chirps/create.blade.php
<x-app-layout :title="__('Create Chirp')">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <a href="{{ route('chirps.index') }}" class="underline underline-offset-2 text-indigo-600">Chirps</a> <span class="text-gray-300">/</span> {{ __('New Chirp') }}
        </h2>
    </x-slot>

    <div class="max-w-2xl mx-auto p-4 sm:p-6 lg:p-8">
        <form action="{{ route('chirps.store') }}" method="POST">
            <textarea
                name="message"
                placeholder="What's on your mind?"
                class="block w-full border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm"
            ></textarea>
            <x-input-error :messages="$errors->get('message')" class="mt-2" />

            <x-primary-button class="mt-4">
                {{ __('Chirp') }}
            </x-primary-button>
        </form>
    </div>
</x-app-layout>
```

Since we're passing a `title` prop to the layout component, we need to update the `AppLayout.php` PHP class:

```php
<?php

namespace App\View\Components;

use Illuminate\View\Component;

class AppLayout extends Component
{
    public function __construct(public ?string $title = null)
    {
    } // [tl! add:-2,3]

    /**
     * Get the view / contents that represents the component.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('layouts.app');
    }
}
```

Now, update the `layouts/app.blade.php` file to make use of the new title prop:

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>
        <title>{{ $title ?? config('app.name', 'Laravel') }}</title> <!-- [tl! remove:-1,1 add] -->

        <!-- Fonts -->
        <link rel="stylesheet" href="https://fonts.bunny.net/css2?family=Nunito:wght@400;600;700&display=swap">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <!-- [tl! collapse:start] -->
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')
            @include('layouts.notifications')

            <!-- Page Heading -->
            <header class="bg-white shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>
        <!-- [tl! collapse:end] -->
    </body>
</html>
```

That's it! Refresh the page in your browser to see your new form rendered in the default layout provided by Breeze!

![Creating Chirps Link](/images/creating-chirps-link.png)

If you click on that link, you will see the form to create Chirps and the breadcrumbs should also have been updated:

![Creating Chirps Form](/images/creating-chirps-form.png)

### Navigation menu

Let's take a moment to add a link to the navigation menu provided by Breeze.

Update the `navigation` Blade component provided by Breeze to add a menu item for desktop screens:

```blade filename=resources/views/components/navigation.blade.php
<!-- Navigation Links -->
<div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
        {{ __('Dashboard') }}
    </x-nav-link>

    <x-nav-link :href="route('chirps.index')" :active="request()->routeIs('chirps.*')">
        {{ __('Chirps') }}
    </x-nav-link> <!-- [tl! add:-2,3] -->
</div>
```

Don't forget the responsive menu used for devices with small screens:

```blade filename=resources/views/components/navigation.blade.php
<div class="pt-2 pb-3 space-y-1">
    <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
        {{ __('Dashboard') }}
    </x-responsive-nav-link>

    <x-responsive-nav-link :href="route('chirps.index')" :active="request()->routeIs('chirps.*')">
        {{ __('Chirps') }}
    </x-responsive-nav-link> <!-- [tl! add:-2,3] -->
</div>
```

We should see the Chirps link on the page nav now:

![Chirps Nav Link](/images/creating-chirps-nav-link.png)

## Saving the Chirp

Our form has been configured to post messages to the `chirps.store` route that we created earlier. Let's update the `store` action on our `ChirpController` class to validate the data and create a new Chirp:

```php filename=app/Http/Controllers/ChirpController.php
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
        return view('chirps.index');
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
        //
        $validated = $request->validate([ // [tl! remove:-1,1 add:start]
            'message' => ['required', 'string', 'max:255'],
        ]);

        $request->user()->chirps()->create($validated);

        return redirect()->route('chirps.index'); // [tl! add:end]
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
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Chirp  $chirp
     * @return \Illuminate\Http\Response
     */
    public function destroy(Chirp $chirp)
    {
        //
    }
    // [tl! collapse:end]
}
```

We're using Laravel's powerful validation feature to ensure that the user provides a message and that it won't exceed the 255 character limit of the database column we'll be creating.

We're then creating a record that will belong to the logged in user by leveraging a `chirps` relationship. We will define that relationship soon.

Finally, we can return a redirect response to our `chirps.index` route.

### Creating a relationship

You may have noticed in the previous step that we called a `chirps` method on the `$request->user()` object. We need to create this method on our `User` model to define a ["has many"](https://laravel.com/docs/eloquent-relationships#one-to-many) relationship:

```php
<?php
// [tl! collapse:start]
namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
// [tl! collapse:end]
class User extends Authenticatable
{
    // [tl! collapse:start]
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function chirps()
    {
        return $this->hasMany(Chirp::class);
    } // [tl! add:-3,4]
}
```

Laravel offers many different types of model relationships that you can read more about in the [Eloquent Relationships](https://laravel.com/docs/eloquent-relationships) documentation.

### Mass assignment protection

Passing all of the data from a request to your model can be risky. Imagine you have a page where users can edit their profiles. If you were to pass the entire request to the model, then a user could edit any column they like, such as an `is_admin` column. This is called a [mass assignment vulnerability](https://en.wikipedia.org/wiki/Mass_assignment_vulnerability).

Laravel protects you from accidentally doing this by blocking mass assignment by default. Mass assignment is very convenient though, as it prevents you from having to assign each attribute one-by-one. We can enable mass assignment for safe attributes by marking them as "fillable".

Let's add the `$fillable` property to our `Chirp` model to enable mass-assignment for the `message` attribute:

```php filename=app/Models/Chirp.hp
<?php
// [tl! collapse:start]
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// [tl! collapse:end]
class Chirp extends Model
{
    use HasFactory;

    protected $fillable = [
        'message',
    ]; // [tl! add:-2,3]
}
```

You can learn more about Laravel's mass assignment protection in the [documentation](https://laravel.com/docs/eloquent#mass-assignment).

### Updating the migration

The only thing missing is extra columns in our database to store the relationship between a `Chirp` and its `User` and the message itself. Remember the database migration we created earlier? It's time to open that file to add some extra columns:

```php filename=database/migrations/{timestamp}_create_chirps_table.php
<?php
// [tl! collapse:start]
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
// [tl! collapse:end]
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chirps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('message'); // [tl! add:-1,2]
            $table->timestamps();
        });
    }
    // [tl! collapse:start]
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('chirps');
    }
    // [tl! collapse:end]
};
```

We haven't migrated the database since we added this migration, so let do it now:

```bash
php artisan migrate
```

Each database migration will only be run once. To make additional changes to a table, you will need to create another migration. During development, you may wish to update an undeployed migration and rebuild your database from scratch using the `php artisan migrate:fresh` command.

### Testing it out

We're now ready to send a Chirp using the form we just created! We won't be able to see the result yet because we haven't displayed existing Chirps on the page.

![Saving Chirps](/images/creating-chirps-saving.png)

If you leave the message field empty, or enter more than 255 characters, then you'll see the validation in action.

### Artisan Tinker

This is great time to learn about [Artisan Tinker](https://laravel.com/docs/artisan#tinker), a _REPL_ ([Read-eval-print loop](https://en.wikipedia.org/wiki/Read%E2%80%93eval%E2%80%93print_loop)) where you can execute arbitrary PHP code in your Laravel application.

In your console, start a new tinker session:

```bash
php artisan tinker
```

Next, execute the following code to display the Chirps in your database:

```php
App\Model\Chirp::all();
```

```bash
=> Illuminate\Database\Eloquent\Collection {#4634
     all: [
       App\Models\Chirp {#4636
         id: 1,
         user_id: 1,
         message: "Testing this out!",
         created_at: "2022-09-27 02:41:03",
         updated_at: "2022-09-27 02:41:03",
       },
     ],
   }
```

You may exit Tinker by using the `exit` command, or by pressing `Ctrl` + `c`.

## Flash Messages

Before we move on from creating Chirps, let's add the ability to show flash messages to the users. This may be useful to tell them that something happened in our app.

Since we're redirecting the user to another page and redirects happens in the browser (client side), we'd need a way to store messages across requests. Laravel has a feature called [Flash Data](https://laravel.com/docs/session#flash-data) which does exactly that! With that, we can safely store a flash message in the user's session, just so we can retrive it from there after the redirect happens in the user's browser.

Let's update our `store` action in the `ChirpController` to also return a flash message named `status` in the redirect:

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
        return view('chirps.index');
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

        $request->user()->chirps()->create($validated);

        return redirect()
            ->route('chirps.index');
            ->route('chirps.index')
            ->with('status', __('Chirp created.')); // [tl! remove:-2,1 add:-1,2]
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
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Chirp  $chirp
     * @return \Illuminate\Http\Response
     */
    public function destroy(Chirp $chirp)
    {
        //
    }
    // [tl! collapse:end]
}
```

Then, let's change our `layouts.app` file to include a `layouts.notifications` partial:

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <!-- [tl! collapse:start] -->
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="stylesheet" href="https://fonts.bunny.net/css2?family=Nunito:wght@400;600;700&display=swap">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <!-- [tl! collapse:end] -->
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')
            @include('layouts.notifications') <!-- [tl! add]-->
            <!-- [tl! collapse:start] -->
            <!-- Page Heading -->
            <header class="bg-white shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
            <!-- [tl! collapse:end] -->
        </div>
    </body>
</html>
```

Next, let's create the `layouts.notifications` wrapper partial:

```blade
<div id="notifications" class="fixed top-10 left-0 right-0 flex flex-col items-center justify-center z-10 opacity-80">
    @if (session()->has('status'))
        @include('layouts.notification', ['message' => session('status')])
    @endif
</div>
```

So, each notification will render with the `layouts.notification` (singular) partial and will be added to the wrapper partial. Let's add the indivitual notification partial:

```blade
<div class="py-1 px-4 leading-7 text-center text-white rounded-full bg-gray-900 transition-all animate-appear-then-fade-out" data-controller="flash" data-action="animationend->flash#remove">
    {{ $message }}
</div>
```

Now, build our TailwindCSS styles:

```bash
php artisan tailwindcss:build
```

We're making use of our existing `flash` Stimulus controller and also the `animate-appear-then-fade-out` animation CSS class.

![Flash Messages](/images/creating-chirps-flash-messages.png)

[Continue to listing Chirps...](/listing-chirps)
