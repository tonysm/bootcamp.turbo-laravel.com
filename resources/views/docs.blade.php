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
                    @if (! $page || $page === 'introduction')
                        <div class="relative -mr-22 xl:-mr-44 mb-12 sm:mb-20 xl:flex xl:justify-between xl:items-center space-y-4 mb-12">
                            <div class="mt-6 xl:mt-8 relative xl:static z-10">
                                <h1>
                                    <small class="text-xl font-medium leading-10 text-gray-900 dark:text-white">Learn</small>
                                    <br />
                                    <span class="mt-4 text-4xl sm:text-5xl font-bold text-gray-900 dark:text-white">Hotwire</span>
                                    <br />
                                    <span class="text-4xl sm:text-5xl font-bold text-red-600">for Web Artisans</span>
                                </h1>

                                <div class="mb-6 sm:mt-0 absolute bottom-0 xl:static">
                                    <div class="xl:-mr-24 p-3 sm:p-6 flex sm:inline-flex xl:flex space-x-2 rounded-lg border relative bg-white dark:bg-dark-500 shadow-xl z-[1]">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-600 -scale-x-100" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                        </svg>
                                        <div class="flex-1">
                                            <div class="flex justify-between">
                                                <div>
                                                    <span class="text-sm font-medium sm:text-sm text-gray-800 dark:text-gray-200">Tony Messias</span>
                                                    <br class="sm:hidden">
                                                    <small class="sm:ml-2 text-xs sm:text-sm text-gray-600 dark:text-gray-500">just now</small>
                                                </div>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                                                </svg>
                                            </div>
                                            <p class="mt-2 mb-0 sm:mt-4 mr-1 sm:mr-0 text-sm sm:text-md text-gray-900 dark:text-gray-100">Let's build something with Turbo Laravel!</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <img class="rounded-lg shadow-lg hidden xl:block rotate-3 rounded-md -translate-x-12 w-[420px]" src="{{ asset('/images/intro-code-showcase.png') }}" alt="Example code" />
                        </div>
                    @endif

                    @include($page)
                </main>
            </div>
        </div>
    </body>
</html>
