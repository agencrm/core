<?php

// routes/api.php


// Facades
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

//
use App\Models\EntityValue;

// API Controllers
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\FileController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\LabelController;
use App\Http\Controllers\Api\LabelGroupController;
use App\Http\Controllers\Api\FlowController;
use App\Http\Controllers\Api\ViewController;
use App\Http\Controllers\Webhook\WebhookController;
use App\Http\Controllers\Api\WebhookHitController;
use App\Http\Controllers\Api\FormController;
use App\Http\Controllers\Api\CommentController;

// PUBLIC ROUTES
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);


//
Route::name('api.')->group(function () {

    // PROTECTED ROUTES
    Route::middleware(['auth:sanctum'])->group(function () {

    });

    // Users
    Route::get('/user', [UserController::class, 'me'])->name('users.me');

    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/{id}', [UserController::class, 'show'])->name('show');
    });

    // Files
    Route::prefix('files')->name('files.')->middleware('auth:sanctum')->group(function () {
        Route::get('/', [FileController::class, 'index'])->name('index');
        Route::get('/{id}', [FileController::class, 'show'])->name('show');
        Route::post('/', [FileController::class, 'store'])->name('store');
        Route::delete('/{id}', [FileController::class, 'destroy'])->name('destroy');
    });

    // Mesages
    Route::prefix('messages')->name('messages.')->middleware('auth:sanctum')->group(function () {
        Route::get('/', [MessageController::class, 'index'])->name('index');
        Route::get('/{id}', [MessageController::class, 'show'])->name('show');
        Route::post('/', [MessageController::class, 'store'])->name('store');
        Route::delete('/{id}', [MessageController::class, 'destroy'])->name('destroy');
    });

    // Contacts
    Route::prefix('contacts')->name('contacts.')->group(function () {
        Route::get('/', [ContactController::class, 'index'])->name('index');
        Route::get('/{id}', [ContactController::class, 'show'])->name('show');
        Route::post('/', [ContactController::class, 'store'])->name('store');
        Route::delete('/{id}', [ContactController::class, 'destroy'])->name('destroy');
    });

    // Labels
    Route::prefix('labels')->name('labels.')->group(function () {
        Route::get('/', [LabelController::class, 'index'])->name('index');
        Route::get('/{id}', [LabelController::class, 'show'])->name('show');
        Route::post('/', [LabelController::class, 'store'])->name('store');
        Route::patch('/{id}', [LabelController::class, 'update'])->name('update'); // ✅ ADD THIS
        Route::delete('/{id}', [LabelController::class, 'destroy'])->name('destroy');
    });
    
    // Labels Groups
    Route::prefix('label-groups')->name('label-groups.')->middleware('auth:sanctum')->group(function () {
        Route::get('/', [LabelGroupController::class, 'index'])->name('index');
        Route::get('/{id}', [LabelGroupController::class, 'show'])->name('show');
        Route::post('/', [LabelGroupController::class, 'store'])->name('store');
        Route::patch('/{id}', [LabelGroupController::class, 'update'])->name('update'); // ✅ ADD THIS
        Route::delete('/{id}', [LabelGroupController::class, 'destroy'])->name('destroy');
    });
    
    // Flows
    Route::prefix('flows')->name('flows.')->middleware('auth:sanctum')->group(function () {
        Route::get('/', [FlowController::class, 'index'])->name('index');     // GET /api/flows
        Route::get('/{id}', [FlowController::class, 'show'])->name('show');  // GET /api/flows/{id}
        Route::post('/', [FlowController::class, 'store'])->name('store');   // POST /api/flows
        Route::patch('/{id}', [FlowController::class, 'update'])->name('update'); // PATCH /api/flows/{id}
        Route::delete('/{id}', [FlowController::class, 'destroy'])->name('destroy'); // DELETE /api/flows/{id}
    });

    // Comments
    Route::prefix('comments')->name('comments.')->middleware('auth:sanctum')->group(function () {
        Route::get('/', [CommentController::class, 'index'])->name('index');     // GET /api/comments
        Route::get('/{id}', [CommentController::class, 'show'])->name('show');  // GET /api/comments/{id}
        Route::post('/', [CommentController::class, 'store'])->name('store');   // POST /api/comments
        Route::patch('/{id}', [CommentController::class, 'update'])->name('update'); // PATCH /api/comments/{id}
        Route::delete('/{id}', [CommentController::class, 'destroy'])->name('destroy'); // DELETE /api/comments/{id}
    });

    // Forms
    Route::prefix('forms')->name('forms.')->group(function () {
        Route::get('/', [FormController::class, 'index'])->name('index');
        Route::get('/{id}', [FormController::class, 'show'])->name('show');
        Route::post('/', [FormController::class, 'store'])->name('store');
        Route::delete('/{id}', [FormController::class, 'destroy'])->name('destroy');
    });

    // View
    Route::prefix('views')->name('views.')->middleware('auth:sanctum')->group(function () {
        Route::get('/', [ViewController::class, 'index'])->name('index');
        Route::post('/', [ViewController::class, 'store'])->name('store');
        Route::get('/{id}', [ViewController::class, 'show'])->name('show');
        Route::put('/{id}', [ViewController::class, 'update'])->name('update');
        Route::delete('/{id}', [ViewController::class, 'destroy'])->name('destroy');
    });

    // Webhooks
    Route::prefix('webhooks')->name('webhooks.')->group(function () {
        Route::prefix('hits')->name('hits.')->middleware('auth:sanctum')->group(function () {
            Route::get('/', [WebhookHitController::class, 'index'])->name('index');     // api.webhooks.hits.index
            Route::get('/{id}', [WebhookHitController::class, 'show'])->name('show');   // api.webhooks.hits.show
            Route::delete('/{id}', [WebhookHitController::class, 'destroy'])->name('destroy');
        });
    }); 
        
    Route::patch('/fields/{model}/{id}', function (
        Request $request,
        string $model,
        int $id
    ) {
        Log::debug('[PATCH] /fields/{model}/{id} → Incoming request', [
            'model_param' => $model,
            'model_id' => $id,
            'request_payload' => $request->all(),
            'user_id' => $request->user()?->id,
        ]);
    
        // $validated = $request->validate([
        //     'key' => 'required|string',
        //     'value' => 'nullable|string',
        // ]);

        $validated = $request->validate([
            'key' => 'required|string',
            'value' => 'nullable', // ✅ allow any scalar (number or string)
        ]);
    
        $modelClass = '\\App\\Models\\' . ucfirst($model);
    
        if (!class_exists($modelClass)) {
            Log::warning('[PATCH] /fields → Invalid model class', [
                'model_param' => $model,
                'resolved_class' => $modelClass,
                'key' => $validated['key'],
                'value' => $validated['value'],
            ]);
    
            return response()->json(['error' => 'Model not found'], 404);
        }
    
        Log::info('[PATCH] /fields → Updating field value', [
            'model_class' => $modelClass,
            'entity_id' => $id,
            'field_key' => $validated['key'],
            'field_value' => $validated['value'],
            'user_id' => $request->user()?->id,
        ]);
    
        $modelClass = '\\App\\Models\\' . ucfirst($model);

        if (!class_exists($modelClass)) {
            Log::warning('[PATCH] /fields → Invalid model class', [
                'attempted_class' => $modelClass,
            ]);
            return response()->json(['error' => 'Model not found'], 404);
        }
        
        $modelInstance = $modelClass::findOrFail($id);
        
        if (!method_exists($modelInstance, 'setField')) {
            Log::error("Model $modelClass does not use HasEntityValues.");
            return response()->json(['error' => 'Model does not support dynamic fields'], 400);
        }
        
        $field = $modelInstance->setField($validated['key'], $validated['value']);
        
    
        Log::debug('[PATCH] /fields → Update result', [
            'entity_id' => $id,
            'field_id' => $field->id ?? null,
            'field_data' => $field->toArray(),
        ]);
    
        return response()->json(['success' => true, 'field' => $field]);
    })->middleware('auth:sanctum')->name('fields.update');
    
    

    //
    Route::get('/token', function () {
        $user = \App\Models\User::first();
        return $user->createToken('check')->plainTextToken;
    });

});