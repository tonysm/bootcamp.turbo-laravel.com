<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}" />

        <title>{{ config('app.name', 'Turbo Laravel Bootcamp') }}</title>

        <link rel="stylesheet" href="{{ tailwindcss('css/app.css') }}">
        <x-importmap-tags />
    </head>
    <body class="antialiased bg-white min-h-screen">
        <div class="relative min-h-screen">
            <div class="hidden md:block fixed top-0 right-0 px-6 py-4">
                <div class="flex items-center space-x-4">
                    <a href="https://turbo-laravel.com" class="text-sm text-gray-900 dark:text-gray-500 underline">
                        Docs
                    </a>
                </div>
            </div>

            <div class="w-full md:mx-auto md:flex">
                <nav class="md:hidden p-4 border-b" data-controller="dropdown" data-dropdown-css-class="hidden">
                    <div class="relative">
                        <ul class="flex items-center justify-between">
                            <li><span class="text-lg lg:text-xl font-semibold">Turbo Laravel Bootcamp</span></li>
                            <li><button data-action="click->dropdown#toggle"><x-icons.bars-3 /></button></li>
                        </ul>

                        <div
                            data-dropdown-target="content"
                            class="hidden transition transform space-y-2"
                            data-transition-enter="transition ease-out duration-200"
                            data-transition-enter-start="transform opacity-0 scale-95"
                            data-transition-enter-end="transform opacity-100 scale-200"
                            data-transition-leave="transition ease-in duration-75"
                            data-transition-leave-start="transform opacity-100 scale-200"
                            data-transition-leave-end="transform opacity-0 scale-95"
                        >
                            <ul class="flex text-sm flex-col mt-5 py-5 border-y border-gray-200 space-y-4 mb-2">
                                <li class="transition transform translate-x-0 hover:translate-x-2"><a class="font-medium" href="https://github.com/tonysm/turbo-laravel">GitHub</a></li>
                                <li class="transition transform translate-x-0 hover:translate-x-2"><a class="font-medium" href="https://turbo-laravel.com">Docs</a></li>
                            </ul>

                            <div data-controller="nav" data-nav-highlight-target="nav" data-action="click->dropdown#close" class="nav">
                                @include('nav')
                            </div>
                        </div>
                    </div>
                </nav>
                <aside class="hidden min-h-screen bg-gray-100 md:block w-1/4 py-4 px-2 lg:px-8 lg:py-8 shrink-0">
                    <nav class="nav fixed" data-controller="nav">
                        <div class="no-prose mb-8">
                            <p class="text-xl lg:text-3xl font-bold">Turbo Laravel<br> Bootcamp</p>
                        </div>
                        @include('nav')
                    </nav>
                </aside>

                <main id="main-content" class="flex-1 py-4 px-2 lg:px-24 lg:py-24 bg-white min-h-screen prose prose-pre:p-0 prose-pre:mr-4 w-full max-w-4xl">
                    <div class="mb-6 p-4 rounded bg-blue-50 border border-blue-100 shadow-md">
                        <strong>Hey, there! 👋🏻</strong> This Bootcamp is still under construction. I'm sharing it publicly because I think it's already in a good enough shape, but here be dragons. If you find anything not working, please let me know and I'll fix it. I feel like the web part is complete, but the Turbo Native side wasn't reviewed yet. Enjoy!
                    </div>

                    @include($page)

                    <footer class="pt-20">
                        <p class="text-center">
                            Maintained by <a href="mailto:tonysm@hey.com" class="font-semibold underline underline-offset-4">Tony Messias</a>. <br class="sm:hidden" /> Code highlighting by <a href="https://torchlight.dev" class="font-semibold underline underline-offset-4">Torchlight</a>.
                        </p>
                    </footer>
                </main>
            </div>
        </div>
    </body>
</html>
