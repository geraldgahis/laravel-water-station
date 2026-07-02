<?php

use Illuminate\Support\Facades\Route;


Route::livewire('/', 'pages::auth.login')->name('login');



Route::middleware(['auth'])->group(function () {
    Route::livewire('/dashboard', 'pages::dashboard')->name('dashboard');
    

    // Customers route
    Route::livewire('/customers', 'pages::customers.index')->name('customers.index');
    Route::livewire('/customers/create', 'pages::customers.create')->name('customers.create');
    Route::livewire('/customers/{customer}/edit', 'pages::customers.edit')->name('customers.edit');
    Route::livewire('/customers/{customer}', 'pages::customers.show')->name('customers.show');

    //Product / Inventory Routes
    Route::livewire('/products', 'pages::products.index')->name('products.index');
    Route::livewire('/products/create', 'pages::products.create')->name('products.create');
    Route::livewire('/products/{product}/edit', 'pages::products.edit')->name('products.edit');

    // Orders route
    Route::livewire('/orders', 'pages::orders.index')->name('orders.index');
    Route::livewire('/orders/create', 'pages::orders.create')->name('orders.create');
    Route::livewire('/orders/{order}/edit', 'pages::orders.edit')->name('orders.edit');

    // Reports route
    Route::livewire('/reports', 'pages::reports.index')->name('reports.index');
});