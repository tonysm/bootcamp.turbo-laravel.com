# Introduction

Learn how to make web apps using Hotwire techniques. Next, learn how it may help you bridge the Web and Native (Android) Worlds!

To explore the many sides of Hotwire, we're gonna build a microblogging platform called Turbo Chirper. Many parts of this tutorial were inspired by the Laravel Bootcamp and adapted to fit better in a Hotwired app.

We're gonna be using Importmap Laravel and TailwindCSS Laravel instead of Laravel's default Vite setup. It would work fine with Vite, but I'm taking this opportunity to demonstrate alternative front-end setups. If you're already familiar with Vite, feel free to skip the installation parts about Importmap Laravel and TailwindCSS Laravel.

On the JavaScript side, we're gonna be using Stimulus.js. Laravel Breeze chips with a few tiny Alpine.js components, which we will be converting to Stimulus. Alpine would work fine for most things. The first version of this tutorial used Alpine. But I later converted everything to Stimulus once I got to the Native bridge integration. I liked it better with Stimulus in this context. Again, if you're already familiar with Alpine, feel free keep using it and adapt the examples to fit it.

Let's get started!

## Web

In the Web Tutorial, we're gonna build our [majestic main web app](https://m.signalvnoise.com/the-majestic-monolith/) using Laravel and Turbo Laravel that will serve as basis for the second part of the tutorial which focuses on the Native/Android side of Turbo.

[Start the Web Tutorial...](/installation)

## Native

The Native Tutorial (second part of bootcamp) is aimed at showcasing the Turbo Native side of Hotwire. We're gonna use Android and Kotlin to build a fully native wrapper around our web app and [progressively enhance the UX for mobile users](https://m.signalvnoise.com/basecamp-3-for-ios-hybrid-architecture/).

[Start the Native Tutorial...](/native-installation)
