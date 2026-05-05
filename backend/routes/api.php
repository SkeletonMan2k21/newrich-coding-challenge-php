<?php

declare(strict_types=1);

use App\Http\Controllers\ChallengeItemsController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ChallengeItemsController::class, 'status']);
Route::get('/items', [ChallengeItemsController::class, 'items']);
Route::get('/active-names', [ChallengeItemsController::class, 'activeNames']);
