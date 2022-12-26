# Editing Chirps

[TOC]

## Introduction

Let's add a feature that's missing from other popular bird-themed microblogging platforms — the ability to edit Chirps!

## Routing

First we will update our routes file to enable the `chirps.edit` and `chirps.update` routes for our resource controller:

```php filename="routes/web.php"
<?php
// [tl! collapse:start]
use App\Http\Controllers\ChirpController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

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

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');
// [tl! collapse:end]
Route::resource('chirps', ChirpController::class)
    ->only(['index', 'create', 'store'])
    ->only(['index', 'create', 'store', 'edit', 'update']) // [tl! remove:-1,1 add]
    ->middleware(['auth', 'verified']);
// [tl! collapse:start]
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php'; // [tl! collapse:end]
```

Our route table for this controller now looks like this:

Verb      | URI                    | Action       | Route Name
----------|------------------------|--------------|---------------------
GET       | `/chirps`              | index        | `chirps.index`
GET       | `/chirps/create`       | create       | `chirps.create`
POST      | `/chirps`              | store        | `chirps.store`
GET       | `/chirps/{chirp}/edit` | edit         | `chirps.edit`
PUT/PATCH | `/chirps/{chirp}`      | update       | `chirps.update`

## Updating our partial

Next, let's update our `chirps._chirp` Blade partial to have an edit form for existing Chirps.

We're going to use the `<x-dropdown>` component that comes with Breeze, which we'll only display to the Chirp author. We'll also display an indication if a Chirp has been edited by comparing the Chirp's `created_at` date with its `updated_at` date:

```blade filename="resources/views/chirps/_chirp.blade.php"
<div class="p-6 flex space-x-2">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-600 -scale-x-100" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
    </svg>
    <div class="flex-1">
        <div class="flex justify-between items-center">
            <div>
                <span class="text-gray-800">{{ $chirp->user->name }}</span>
                <small class="ml-2 text-sm text-gray-600">
                    <x-relative-time :date="$chirp->created_at" />
                </small>
                @unless ($chirp->created_at->eq($chirp->updated_at))<!-- [tl! add:start] -->
                <small class="text-sm text-gray-600"> &middot; edited</small>
                @endunless<!-- [tl! add:end] -->
            </div>
            <!-- [tl! add:start] -->
            @if (Auth::id() === $chirp->user->id)
            <x-dropdown align="right" width="48">
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
                </x-slot>
            </x-dropdown>
            @endif<!-- [tl! add:end] -->
        </div>
        <p class="mt-4 text-lg text-gray-900">{{ $chirp->message }}</p>
    </div>
</div>
```

## Showing the edit Chirp form

We can now update the `edit` action on our `ChirpController` class to show the form to edit Chirps. Even though we're only displaying the edit button to the author of the Chirp, we also need to authorize the request to make sure it's actually the author that is updating it:

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

        $request->user()->chirps()->create($validated);

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
    // [tl! collapse:end]
    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Chirp  $chirp
     * @return \Illuminate\Http\ResponseApp\
     */
    public function edit(Chirp $chirp)
    {
        //
        $this->authorize('update', $chirp);// [tl! remove:-1,1 add:start]

        return view('chirps.edit', [
            'chirp' => $chirp,
        ]);// [tl! add:end]
    }
    // [tl! collapse:start]
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

Now, we need to create our `chirps.edit` view:

```blade filename="resources/views/chirps/edit.blade.php"
<x-app-layout :title="__('Edit Chirp')">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <a href="{{ route('chirps.index') }}" class="underline underline-offset-2 text-indigo-600">Chirps</a> <span class="text-gray-300">/</span> {{ __('Edit Chirp #:id', ['id' => $chirp->id]) }}
        </h2>
    </x-slot>

    <div class="max-w-2xl mx-auto p-4 sm:p-6 lg:p-8">
        @include('chirps._form', ['chirp' => $chirp])
    </div>
</x-app-layout>
```

Since this view would use the same form as the create one, we may extract the form to its own partial at `chirps._form` and make some changes to it so it works for both creating and editing Chirps:

```blade filename="resources/views/chirps/_form.blade.php"
<form action="{{ ($chirp ?? false) ? route('chirps.update', $chirp) : route('chirps.store') }}" method="POST">
    @if ($chirp ?? false)
        @method('PUT')
    @endif

    <textarea
        name="message"
        placeholder="What's on your mind?"
        class="block w-full border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm"
    >{{ $chirp->message ?? '' }}</textarea>
    <x-input-error :messages="$errors->get('message')" class="mt-2" />

    <div class="flex items-center justify-start space-x-2">
        <x-primary-button class="mt-4">
            {{ __('Chirp') }}
        </x-primary-button>

        @if ($chirp ?? false)
        <a href="{{ route('chirps.index') }}" class="mt-4">Cancel</a>
        @endif
    </div>
</form>
```

Now, we can update the `chirps.create` view to also use the same form:

```blade filename="resources/views/chirps/create.blade.php"
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <a href="{{ route('chirps.index') }}" class="underline underline-offset-2 text-indigo-600">Chirps</a> <span class="text-gray-300">/</span> {{ __('New Chirp') }}
        </h2>
    </x-slot>

    <div class="max-w-2xl mx-auto p-4 sm:p-6 lg:p-8">
        <form action="{{ route('chirps.store') }}" method="POST"><!-- [tl! remove:start] -->
            <textarea
                name="message"
                placeholder="What's on your mind?"
                class="block w-full border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm"
            ></textarea>
            <x-input-error :messages="$errors->get('message')" class="mt-2" />

            <x-primary-button class="mt-4">
                {{ __('Chirp') }}
            </x-primary-button>
        </form><!-- [tl! remove:end] -->
        @include('chirps._form')<!-- [tl! add] -->
    </div>
</x-app-layout>
```

## Authorization

By default, the `authorize` method will prevent everyone from being able to update the Chirp. We can specify who is allowed to update it by creating a [Model Policy](https://laravel.com/docs/authorization#creating-policies) with the following command:

```bash
php artisan make:policy ChirpPolicy --model=Chirp
```

This will create a policy class at `app/Policies/ChirpPolicy.php` which we can update to specify that only the author is authorized to update a Chirp:

```php filename="app/Policies/ChirpPolicy.php"
<?php
// [tl! collapse:start]
namespace App\Policies;

use App\Models\Chirp;App\
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
// [tl! collapse:end]
class ChirpPolicy
{
    // [tl! collapse:start]
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        //
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Chirp  $chirp
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Chirp $chirp)
    {
        //
    }

    /**App\
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        //
    }
    // [tl! collapse:end]
    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Chirp  $chirp
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Chirp $chirp)
    {
        //
        return $user->is($chirp->user);// [tl! remove:-1,1 add]
    }
    // [tl! collapse:start]
    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Chirp  $chirp
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Chirp $chirp)
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Chirp  $chirp
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Chirp $chirp)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Chirp  $chirp
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Chirp $chirp)
    {
        //
    }
    // [tl! collapse:end]
}
```

You should be able to use the dropdown, click on the Edit link and view the edit Chirp form:

![Edit Chirp Form](/images/editing-chirps-form.png)

## Updating Chirps

We can now change the `update` action on our `ChirpController` class to validate the request and update the database. Again, we also need to authorize the request here to make sure it's actually the author that is updating it:

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
     * @return \Illuminate\Http\ResponseApp\
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

        $request->user()->chirps()->create($validated);

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
        //
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
        //
        $this->authorize('update', $chirp);// [tl! remove:-1,1 add:start]

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:255'],
        ]);

        $chirp->update($validated);

        return redirect()
            ->route('chirps.index')
            ->with('status', __('Chirp updated.'));// [tl! add:end]
    }
    // [tl! collapse:start]
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

You may have noticed the validation rules are duplicated with the `store` action. You might consider extracting them using Laravel's [Form Request Validation](https://laravel.com/docs/validation#form-request-validation), which makes it easy to re-use validation rules and to keep your controllers light.

## Testing it out

Time to test it out! Go ahead and edit a few Chirps using the dropdown menu. If you register another user account, you'll see that only the author of a Chirp can edit it.

![Changed Chirps](/images/editing-chirps-changed.png)

[Continue to deleting Chirps...](/deleting-chirps)
