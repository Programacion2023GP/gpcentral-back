<?php

namespace App\Http\Controllers;

use App\Models\EmployeeDetail;

class EmployeeDetailController extends BaseCrudController
{
    protected $modelClass = EmployeeDetail::class;
    protected $imageDirectory = 'employees/details';
    protected $imageFields = ['avatar', 'signature_image'];
    protected $selectLabel = ['CONCAT(name, " ", plast_name)'];

    public function __construct()
    {
        $this->validationRules = [
            'employee_id' => 'required|exists:employees,id',
            'name' => 'required|string|max:255',
            'plast_name' => 'required|string|max:255',
            'mlast_name' => 'nullable|string|max:255',
            'rfc' => 'nullable|string|max:20',
            'curp' => 'nullable|string|max:20',
            'gender' => 'nullable|in:M,F',
            'cellphone' => 'nullable|string|max:50',
            'start_date' => 'required|date',
        ];
    }
}
