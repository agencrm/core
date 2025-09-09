<?php

// routes/web.php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\Web\FlowController;

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

Route::get('contacts', function () {
    return Inertia::render('Contacts');
})->middleware(['auth', 'verified'])->name('contacts');

Route::prefix('flows')->name('flows.')->group(function () {
    Route::get('/', [FlowController::class, 'index'])->name('index'); 
    Route::get('/{id}', [FlowController::class, 'show'])->name('show');  // GET /api/flows/{id}
});


Route::get('web', function () {
    return Inertia::render('Web');
})->middleware(['auth', 'verified'])->name('web');


require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
