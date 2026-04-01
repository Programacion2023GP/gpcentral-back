<?php

namespace App\Http\Controllers;

use App\Models\Organization;

class OrganizationController extends BaseCrudController
{
    protected $modelClass = Organization::class;
    protected $validationRules = [
        'code' => 'required|string|max:45|unique:organizations,code',
        'name' => 'required|string|max:255',
    ];
}
