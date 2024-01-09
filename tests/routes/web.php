<?php

use App\Controllers\MainController;
use Horizom\Routing\Facades\Route;

Route::get('/', [MainController::class, 'index']);
