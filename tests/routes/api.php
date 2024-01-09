<?php

use App\Controllers\ApiController;
use Horizom\Routing\Facades\Route;

Route::any('/', [ApiController::class, 'index']);
Route::any('/status', [ApiController::class, 'status']);
Route::any('/version', [ApiController::class, 'version']);
