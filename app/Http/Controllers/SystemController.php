<?php

namespace App\Http\Controllers;

use App\Models\System;

class SystemController extends BaseCrudController
{
    protected $modelClass = System::class;
    protected $selectLabel = 'name';

    public function __construct()
    {
        $this->validationRules = [
            'code' => 'required|string|max:50|unique:systems,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ];
    }
}
