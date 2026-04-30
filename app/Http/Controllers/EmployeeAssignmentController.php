<?php

namespace App\Http\Controllers;

use App\Models\EmployeeAssignment;

class EmployeeAssignmentController extends BaseCrudController
{
    protected $modelClass = EmployeeAssignment::class;

    public function __construct()
    {
        $this->validationRules = [
            'employee_id' => 'required|exists:employees,id',
            'department_uuid' => 'nullable|exists:departments,uuid',
            'position_uuid' => 'required|exists:positions,uuid',
            'start_date' => 'required|date',
        ];
    }
}
