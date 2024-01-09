<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface;

class ApiController
{
    public function index(): ResponseInterface
    {
        return $this->status();
    }

    public function status(): ResponseInterface
    {
        return response()->json(['status' => 'UP']);
    }

    public function version(): ResponseInterface
    {
        return response()->json(['version' => app()->get("version")]);
    }
}
