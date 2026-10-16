<?php

namespace App\Http\Controllers;

use App\Models\ObjResponse;
use App\Models\SyncRun;
use App\Services\EmployeeSyncService;
use Illuminate\Http\Request;

class EmployeeSyncController extends Controller
{
    public function preview(Request $request, EmployeeSyncService $service)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:20480',
            'employment_type' => 'required|in:confianza,sindicalizado',
            'source' => 'required|string|max:100',
        ]);
        return ObjResponse::success($service->preview($request->file('file'), $request->employment_type, $request->source, $request->user()->id));
    }

    public function apply(Request $request, SyncRun $run, EmployeeSyncService $service)
    {
        $request->validate(['change_ids' => 'array', 'change_ids.*' => 'integer']);
        return ObjResponse::success($service->apply($run, $request->input('change_ids', [])), 'Sincronización aplicada');
    }

    public function show(SyncRun $run)
    {
        return ObjResponse::success($run->load('changes', 'requester'));
    }
}
