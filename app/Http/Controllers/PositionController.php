<?php

namespace App\Http\Controllers;

use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PositionController extends BaseCrudController
{
    protected $modelClass = Position::class;
    protected $selectLabel = 'name';

    public function __construct()
    {
        $this->validationRules = [
            // 'department_uuid' => 'nullable|exists:departments,uuid',
            'name' => 'required|string|max:255',
            'parent_position_uuid' => 'nullable|exists:positions,uuid',
            'start_date' => 'required|date',
        ];
    }

    // Sobrescribir store para generar UUID automáticamente
    public function createOrUpdate(Request $request, $id = null)
    {
        if (!$id && !$request->has('uuid')) {
            $request->merge(['uuid' => (string) Str::uuid()]);
        }
        return parent::createOrUpdate($request, $id);
    }
}
