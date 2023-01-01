# *06.* Hotwiring everything

[TOC]

## Introduction

So far, our application is quite basic. Out of Hotwire, we're only using Turbo Drive, which is enabled by default when we install and start Turbo.

## Using Turbo Frames to render the create Chirps form inline

Our application works, but we could improve it. Instead of sending users to a dedicated chirp creation form page, let's display the form inline right on the `chirps.index` page. To do that, we're going to use [lazy-loading Turbo Frames](https://turbo.hotwired.dev/reference/frames):

```blade filename="resources/views/chirps/index.blade.php"
<x-app-layout><!-- [tl! collapse:start] -->
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Chirps') }}
        </h2>
    </x-slot>
    <!-- [tl! collapse:end] -->
    <div class="max-w-2xl mx-auto p-4 sm:p-6 lg:p-8">
        <div class="relative flex items-center justify-center py-10 px-4 rounded-lg border border-dotted border-gray-300"><!-- [tl! remove:start] -->
            <a class="text-gray-700" href="{{ route('chirps.create') }}">
                Add a new Chirp
                <span class="absolute inset-0"></span>
            </a>
        </div><!-- [tl! remove:end] -->
        <x-turbo-frame id="create_chirp" src="{{ route('chirps.create') }}"><!-- [tl! add:start] -->
            <div class="relative flex items-center justify-center py-10 px-4 rounded-lg border border-dotted border-gray-300">
                <a class="text-gray-700" href="{{ route('chirps.create') }}">
                    Add a new Chirp
                    <span class="absolute inset-0"></span>
                </a>
            </div>
        </x-turbo-frame><!-- [tl! add:end] -->
        <!-- [tl! collapse:start] -->
        <div class="mt-6 bg-white shadow-sm rounded-lg divide-y">
            @each('chirps._chirp', $chirps, 'chirp')
        </div>
    </div>
</x-app-layout><!-- [tl! collapse:end] -->
```

For that to work, we also need to wrap our create form with a matching Turbo Frame (by "matching" I mean same DOM ID):

```blade filename=resources/views/chirps/create.blade.php
<x-app-layouts :title="__('Create Chirp')">
    <x-slot name="header"><!-- [tl! collapse:start]-->
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <a href="{{ route('chirps.index') }}" class="underline underline-offset-2 text-indigo-600">Chirps</a> <span class="text-gray-300">/</span> {{ __('New Chirp') }}
        </h2>
    </x-slot><!-- [tl! collapse:end] -->

    <div class="max-w-2xl mx-auto p-4 sm:p-6 lg:p-8">
        @include('chirps._form')<!-- [tl! remove] -->
        <x-turbo-frame id="create_chirp" target="_top"><!-- [tl! add:start] -->
            @include('chirps._form')
        </x-turbo-frame><!-- [tl! add:end] -->
    </div>
</x-app-layout>
```

A few things about this:

1. In the `chirps.index`, we specified the Turbo Frame with the `src` attribute, which indicates Turbo that this is lazy-loading Turbo Frame
1. The Turbo Frame in the `chirps.create` page has a `target="_top"` on it. That's not gonna be used, it's just in case someone opens that page directly by visiting `/chirps/create` or disables JavaScript (in this case, they would still see the link pointing to the create chirps page, so they would be able to use our application normally)

If you try to use the form now, you will see a strange behavior where the form disappears after you submit it and the link is back. If you refresh the page, you'll see the chirp was successfully created.

That happens because we're redirecting users to the `chirps.index` page after the form submission. That page has a matching Turbo Frame, which contains the link. Nothing else on the page changes because of the Turbo Frame contains the page changes to only its fragment.

Let's make use of Turbo Streams to update our form with a clean one and prepend the recently created Chirp to the chirps list.

### Reseting the form and prepeding Chirps to the list

Before we change the `ChirpController`, let's give our list of chirps wrapper element an ID in the `chirps.index` page:

```blade filename=resources/views/chirps/index.blade.php
<x-app-layout><!-- [tl! collapse:start] -->
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Chirps') }}
        </h2>
    </x-slot>

    <div class="max-w-2xl mx-auto p-4 sm:p-6 lg:p-8">
        <x-turbo-frame id="create_chirp" src="{{ route('chirps.create') }}">
            <div class="relative flex items-center justify-center py-10 px-4 rounded-lg border border-dotted border-gray-300">
                <a class="text-gray-700" href="{{ route('chirps.create') }}">
                    Add a new Chirp
                    <span class="absolute inset-0"></span>
                </a>
            </div>
        </x-turbo-frame>
        <!-- [tl! collapse:end] -->
        <div class="mt-6 bg-white shadow-sm rounded-lg divide-y"><!-- [tl! remove] -->
        <div id="chirps" class="mt-6 bg-white shadow-sm rounded-lg divide-y"><!-- [tl! add] -->
            @each('chirps._chirp', $chirps, 'chirp')
        </div>
    </div>
</x-app-layout>
```

Okay, now we can focus update the `store` action in our `ChirpController` to return three Turbo Streams if the client supports it, one to update the form with a clean one, another to prepend the new chirp to the list, and another to append the flash message:

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

        $request->user()->chirps()->create($validated);// [tl! remove]
        $chirp = $request->user()->chirps()->create($validated);// [tl! add:start]

        if ($request->wantsTurboStream()) {
            return turbo_stream([
                turbo_stream($chirp, 'prepend'),
                turbo_stream()->update('create_chirp', view('chirps._form')),
                turbo_stream()->append('notifications', view('layouts.notification', [
                    'message' => __('Chirp created.'),
                ])),
            ]);
        }// [tl! add:end]

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

        return redirect()
            ->route('chirps.index')
            ->with('status', __('Chirp deleted.'));
    }
    // [tl! collapse:end]
}
```

Now if you try creating a Chirp, you should see the newly created Chirp at the top of the chirps list, the form should have been cleared, and a flash message showed up. Nice!

Let's also implement inline editing for our chirps.

## Displaying the edit chirps form inline

To do that, we need to tweak our `chirps._chirp` partial and wrap it with a Turbo Frame. Instead of showing you a long Git diff, replace the existing partial with this one:

```blade filename=resources/views/chirps/_chirp.blade.php
<x-turbo-frame :id="$chirp" class="block p-6">
    <div class="flex space-x-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-600 -scale-x-100" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
        </svg>
        <div class="flex-1">
            <div class="flex justify-between items-center">
                <div>
                    <span class="text-gray-800">{{ $chirp->user->name }}</span>
                    <x-time-ago :date="$chirp->created_at" />
                    @unless ($chirp->created_at->eq($chirp->updated_at))
                    <small class="text-sm text-gray-600"> &middot; edited</small>
                    @endunless
                </div>

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

                        <form action="{{ route('chirps.destroy', $chirp) }}" method="POST">
                            @method('DELETE')
                            <button class="block w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:bg-gray-100 transition duration-150 ease-in-out">
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

Now, let's also update the `chirps.edit` page to add a wrapping Turbo Frame around the form there:

```blade filename=resources/views/chirps/edit.blade.php
<x-app-layout :title="__('Edit Chirp')">
    <!-- [tl! collapse:start] -->
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <a href="{{ route('chirps.index') }}" class="underline underline-offset-2 text-indigo-600">Chirps</a> <span class="text-gray-300">/</span> {{ __('Edit Chirp #:id', ['id' => $chirp->id]) }}
        </h2>
    </x-slot>
    <!-- [tl! collapse:end] -->
    <div class="max-w-2xl mx-auto p-4 sm:p-6 lg:p-8">
        @include('chirps._form', ['chirp' => $chirp])<!-- [tl! remove] -->
        <x-turbo-frame :id="$chirp" target="_top"><!-- [tl! add:start] -->
            @include('chirps._form', ['chirp' => $chirp])
        </x-turbo-frame><!-- [tl! add:end] -->
    </div>
</x-app-layout>
```

## Updating inline edit form with the chirps partial

Now, if you try clicking on the edit button, you should see the form appearing inline! If you submit it, you will see the change takes place already. That's awesome, right? Well, not so much. See, we can see the change because after the chirp is updated, the controller redirects the user to the index page and it happens that the chirp is rendered on that page, so it finds a matching Turbo Frame. If that wasn't the case, we would see a strange behavior.

Let's change the `update` action in the `ChirpController` to return a Turbo Stream with the updated Chirp partial if the client supports it:

```php filename=app/Controllers/ChirpController.php
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

        if ($request->wantsTurboStream()) {// [tl! add:start]
            return turbo_stream([
                turbo_stream($chirp),
                turbo_stream()->append('notifications', view('layouts.notification', [
                    'message' => __('Chirp updated.'),
                ])),
            ]);
        }// [tl! add:end]

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
    public function destroy(Request $request, Chirp $chirp)
    {
        $this->authorize('delete', $chirp);

        $chirp->delete();

        return redirect()
            ->route('chirps.index')
            ->with('status', __('Chirp deleted.'));
    }
    // [tl! collapse:end]
}
```

Now, if you try editing a chirp, you should see the same thing as before, but now we're sure that our chirp will just be updated no matter if it's present in the index listing of chirps or not after the form is submitted. Yay!

## Deleting Chirps with Turbo Streams

To finish things up, if you try to deleting a chirp now, you will notice it's gone from the page, but for the wrong reasons. That happens because after deleting a Chirp, we're also redirecting users to the index page and it happens that there's no chirp in there because it's gone from the database. Since Turbo didn't find a matching Turbo Frame, it removes the frame's content! That's not good, right?

Instead, let's update the `destroy` action in our `ChirpController` to respond with a remove Turbo Stream whenever a chirp is deleted and the client supports it:

```php filename=app/Controllers/ChirpController.php
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
     * @param  \Illuminate\Http\Request  $request [tl! add]
     * @param  \App\Models\Chirp         $chirp
     * @return \Illuminate\Http\Response
     */
    public function destroy(Chirp $chirp) // [tl! remove]
    public function destroy(Request $request, Chirp $chirp) // [tl! add]
    {
        $this->authorize('delete', $chirp);

        $chirp->delete();

        if ($request->wantsTurboStream()) { // [tl! add:start]
            return turbo_stream([
                turbo_stream($chirp),
                turbo_stream()->append('notifications', view('layouts.notification', [
                    'message' => __('Chirp deleted.'),
                ])),
            ]);
        } // [tl! add:end]

        return redirect()
            ->route('chirps.index')
            ->with('status', __('Chirp deleted.'));
    }
}
```

## Turbo Stream Flash Macro

So far we've beeing using the default action methods provided by the Turbo Laravel package. The `turbo_stream()` function returns either an instance of a `PendingTurboStreamResponse` or a `MultiplePendingTurboStreamResponse`. Let's add a `flash` macro to the first one to ease generating flash messages Turbo Streams:

```php filename="app/Providers/AppServiceProvider.php"
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Tonysm\TurboLaravel\Http\PendingTurboStreamResponse; // [tl! add]

class AppServiceProvider extends ServiceProvider
{
    // [tl! collapse:start]
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    // [tl! collapse:end]
    public function boot()
    {
        //
        PendingTurboStreamResponse::macro('flash', function ($message) { // [tl! remove:-1,1 add:0,5]
            return turbo_stream()->append('notifications', view('layouts.notification', [
                'message' => $message,
            ]));
        });
    }
}
```

Now, our controllers can be cleaned up a bit:

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
            return turbo_stream([
                turbo_stream($chirp, 'prepend'),
                turbo_stream()->update('create_chirp', view('chirps._form')),
                turbo_stream()->append('notifications', view('layouts.notification', [// [tl! remove:start]
                    'message' => __('Chirp created.'),
                ])),// [tl! remove:end]
                turbo_stream()->flash(__('Chirp created.')),// [tl! add]
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

        if ($request->wantsTurboStream()) {
            return turbo_stream([
                turbo_stream($chirp),
                turbo_stream()->append('notifications', view('layouts.notification', [// [tl! remove:start]
                    'message' => __('Chirp updated.'),
                ])),// [tl! remove:end]
                turbo_stream()->flash(__('Chirp updated.')),
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
                turbo_stream()->append('notifications', view('layouts.notification', [// [tl! remove:start]
                    'message' => __('Chirp deleted.'),
                ])),// [tl! remove:end]
                turbo_stream()->flash(__('Chirp deleted.')),// [tl! add]
            ]);
        }

        return redirect()
            ->route('chirps.index')
            ->with('status', __('Chirp deleted.'));
    }
}
```

Although this is using Macros, we're still using the Turbo Stream actions that ship with Turbo by default. It's also possible to go custom and create your own actions, if you want to.

## Testing it out

With these changes, our application behaves so much better than before! Try it out yourself!

![Inline Editing Forms](/images/hotwiring-chirps-inline-forms.png)

[Continue to setting up the native app...](/native-setup)
