<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected const PER_PAGE_DEFAULT = 10;
    protected const PER_PAGE_COMPACT = 5;

    protected function getFilledData(Request $request, array $fields): array
    {
        $data = [];

        foreach ($fields as $field) {
            if ($request->filled($field)) {
                $data[$field] = $request->input($field);
            }
        }

        return $data;
    }
}