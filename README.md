
# Model / Elements

## Creating

```
php artisan make:model <Model> -m
php artisan make:controller Api/<Model>Controller
php artisan make:resource <Model>Resource
```

## Seeding

```
php artisan make:seeder <Model>Seeder
php artisan make:factory <Model>Factory --model=<Model>
```

Running
```
 php artisan db:seed --class=<Model>Seeder
```

### Add to Model
```
use Illuminate\Database\Eloquent\Factories\HasFactory;
    use HasFactory;
```

### Add to database/seeders/DatabaseSeeder.php
```
use Illuminate\Database\Eloquent\Factories\HasFactory;
    use HasFactory;
```

## API Endpoint
```
Route::prefix('<model>s')->name('<model>s.')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [FileController::class, 'index'])->name('index');
    Route::get('/{id}', [FileController::class, 'show'])->name('show');
    Route::post('/', [FileController::class, 'store'])->name('store');
    Route::delete('/{id}', [FileController::class, 'destroy'])->name('destroy');
});

```

## Web Endpoint
```
Route::prefix('<model>s')->name('<model>s.')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [FileController::class, 'index'])->name('index');
    Route::get('/{id}', [FileController::class, 'show'])->name('show');
    Route::post('/', [FileController::class, 'store'])->name('store');
    Route::delete('/{id}', [FileController::class, 'destroy'])->name('destroy');
});