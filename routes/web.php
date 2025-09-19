<?php

// routes/web.php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\Web\FlowController;
use App\Http\Controllers\Web\FormController;
use App\Http\Controllers\Web\UserController;
use App\Http\Controllers\Web\WebhookController;
use App\Http\Controllers\Web\ContactController;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('users', function () {
    return Inertia::render('Users');
})->middleware(['auth', 'verified'])->name('users');

Route::get('files', function () {
    return Inertia::render('Files');
})->middleware(['auth', 'verified'])->name('files');

Route::get('messages', function () {
    return Inertia::render('Messages');
})->middleware(['auth', 'verified'])->name('messages');

// Route::get('contacts', function () {
//     return Inertia::render('Contacts');
// })->middleware(['auth', 'verified'])->name('contacts');

// Contacts
Route::prefix('contacts')->name('contacts.')->group(function () {
    Route::get('/', [ContactController::class, 'index'])->name('index'); 
    Route::get('/{id}', [ContactController::class, 'show'])->name('show');  // GET /api/contacts/{id}
});

// Flows
Route::prefix('flows')->name('flows.')->group(function () {
    Route::get('/', [FlowController::class, 'index'])->name('index'); 
    Route::get('/{id}', [FlowController::class, 'show'])->name('show');  // GET /api/flows/{id}
});

Route::prefix('forms')->name('forms.')->group(function () {
    Route::get('/', [FormController::class, 'index'])->name('index'); 
    Route::get('/{id}', [FormController::class, 'show'])->name('show');  // GET /api/flows/{id}
});


// Settings
Route::prefix('settings')->name('settings.')->group(function () {

    // Users
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index'); 
        Route::get('/{id}', [UserController::class, 'show'])->name('show');
    });

    // Webhooks
    Route::prefix('webhooks')->name('webhooks.')->group(function () {
        //Route::get('/', [UserController::class, 'index'])->name('index'); 
        Route::get('/hits', [WebhookController::class, 'hits'])->name('hits'); 
        // Route::get('/{id}', [UserController::class, 'show'])->name('show');
    });


});


Route::get('web', function () {
    return Inertia::render('Web');
})->middleware(['auth', 'verified'])->name('web');


require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
