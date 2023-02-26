<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Error Message
     *
     */
    public function messageError($e)
    {
        $bagError = [];

        foreach (json_decode($e->getMessage()) as $message) {
            array_push($bagError, $message[0]);
        }

        return $bagError;
    }
}
