<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}" />

        <title>Laravel</title>

        <link rel="stylesheet" href="{{ tailwindcss('css/app.css') }}">

        <x-importmap-tags />
    </head>
    <body class="antialiased bg-white min-h-screen">
        <div class="relative min-h-screen bg-gray-100 dark:bg-gray-900">
            <div class="hidden md:block fixed top-0 right-0 px-6 py-4">
                <div class="flex items-center space-x-4">
                    <a href="https://turbo-laravel.com" class="text-sm text-gray-900 dark:text-gray-500 underline">
                        Docs
                    </a>
                </div>
            </div>

            <div class="max-w-6xl w-full md:mx-auto py-8 md:flex space-x-2 sm:px-6 lg:px-8">
                <nav class="md:hidden" data-controller="dropdown" data-dropdown-css-class="hidden">
                    <div class="relative">
                        <ul class="flex items-center justify-end">
                            <li><button data-action="click->dropdown#toggle"><x-icons.bars-3 /></button></li>
                        </ul>

                        <div
                            data-dropdown-target="content"
                            class="hidden transition transform divide-y space-y-2 mb-5 px-2"
                            data-transition-enter="transition ease-out duration-200"
                            data-transition-enter-start="transform opacity-0 scale-95"
                            data-transition-enter-end="transform opacity-100 scale-200"
                            data-transition-leave="transition ease-in duration-75"
                            data-transition-leave-start="transform opacity-100 scale-200"
                            data-transition-leave-end="transform opacity-0 scale-95"
                        >
                            <ul class="flex text-sm divide-y flex-col mt-5 pt-5 border-t border-gray-100 space-y-2">
                                <li><a href="https://github.com/tonysm/turbo-laravel">GitHub</a></li>
                                <li><a href="https://turbo-laravel.com">Docs</a></li>
                            </ul>

                            <div data-controller="nav" data-nav-highlight-target="nav" data-action="click->dropdown#close" class="nav" >
                                @include('nav')
                            </div>
                        </div>
                    </div>
                </nav>
                <aside class="hidden md:block w-1/4 shrink-0">
                    <nav class="nav fixed" data-controller="nav">
                        @include('nav')
                    </nav>
                </aside>

                <main id="main-content" class="flex-1 prose prose-pre:p-0">
                    @include($page)
                </main>
            </div>
        </div>
    </body>
</html>
