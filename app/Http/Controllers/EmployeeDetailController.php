<?php

namespace App\Http\Controllers;

use App\Models\EmployeeDetail;

class EmployeeDetailController extends BaseCrudController
{
    protected $modelClass = EmployeeDetail::class;
    protected $imageDirectory = 'employees/details';
    protected $imageFields = ['avatar', 'signature_image'];
    protected $selectLabel = ['CONCAT(first_name, " ", last_name_paternal)'];

    public function __construct()
    {
        $this->validationRules = [
            'employee_id' => 'required|exists:employees,id',
            'first_name' => 'required|string|max:255',
            'last_name_paternal' => 'required|string|max:255',
            'last_name_maternal' => 'nullable|string|max:255',
            'rfc' => 'nullable|string|max:20',
            'curp' => 'nullable|string|max:20',
            'gender' => 'nullable|in:M,F',
            'phone' => 'nullable|string|max:50',
            'start_date' => 'required|date',
        ];
    }
}
