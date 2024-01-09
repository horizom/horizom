<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface;

class MainController
{
    public function index(): ResponseInterface
    {
        return response()->view('main.index');
    }
}
