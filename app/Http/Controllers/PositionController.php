<?php

namespace App\Http\Controllers;

use App\Models\Position;
use Illuminate\Http\Request;

class PositionController extends BaseCrudController
{
    protected $modelClass = Position::class;
    protected $versioned = true;
    protected $selectLabel = 'name';

    public function __construct()
    {
        $this->validationRules = [
            'name' => 'required|string|max:255',
            'parent_position_uuid' => 'nullable|exists:positions,uuid',
            'start_date' => 'required|date',
        ];
    }
}
