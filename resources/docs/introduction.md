# Introduction

Learn how to make web apps using Laravel and Hotwire. When we finish the web app, we'll dig into the Turbo Native side of Hotwire so we can see it to bridge the Web and Native Worlds!

To explore the many sides of Hotwire, we're going to build a microblogging platform called Turbo Chirper. Many parts of this tutorial were inspired by the [official Laravel Bootcamp](https://bootcamp.laravel.com/) and adapted to work better in a Hotwired app.

We're going to use [Importmap Laravel](https://github.com/tonysm/importmap-laravel) and [TailwindCSS Laravel](https://github.com/tonysm/tailwindcss-laravel) instead of Laravel's default Vite setup. Vite would work, but I'm taking this opportunity to demonstrate an alternative front-end setup. If you're already familiar with Vite, feel free to skip the installation parts about Importmap Laravel and TailwindCSS Laravel.

On the JavaScript side, we're going to use [Stimulus.js](https://stimulus.hotwired.dev/). Laravel Breeze chips with a few tiny [Alpine.js](https://alpinejs.dev/) components, which we'll convert to Stimulus. Alpine would work for most things. The first version of this tutorial used Alpine, for instance. But I decided to convert everything to Stimulus once I got to the Turbo Native integration side. I liked it better with Stimulus in this context. Again, if you're already familiar with Alpine, feel free keep using it and adapt the examples as you see fit.

Let's get started!

## Web

In the Web Tutorial, we're gonna build our [majestic web app](https://m.signalvnoise.com/the-majestic-monolith/) using [Laravel](https://laravel.com/) and [Turbo Laravel](https://github.com/tonysm/turbo-laravel) that will serve as basis for the second part of the tutorial which focuses on Turbo Native and Android.

[Start the Web Tutorial...](/installation)

## Native

The second part of this Bootcamp will focus on Turbo Native. The goal is to showcase the Native side of Hotwire. We're going to use Android and Kotlin to build a fully native wrapper around our web app and [progressively enhance the UX for mobile users](https://m.signalvnoise.com/basecamp-3-for-ios-hybrid-architecture/).

[Start the Native Tutorial...](/native-setup)
