<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\ObjResponse;
use Illuminate\Http\Request;

class MenuController extends BaseCrudController
{
    protected $modelClass = Menu::class;
    protected $selectLabel = 'name';

    public function __construct()
    {
        $this->validationRules = [
            'name' => 'required|string|max:100',
            'slug' => 'required|string|max:100|unique:menus,slug',
            'parent_id' => 'nullable|exists:menus,id',
            'route' => 'nullable|string|max:255',
            'required_permission' => 'nullable|exists:permissions,code',
            'sort_order' => 'integer|min:0',
        ];
    }

    public function navigation(Request $request)
    {
        $user = $request->user()->load('role.permissions');
        $items = Menu::whereNull('parent_id')->where('active', true)
            ->with(['children' => fn($query) => $query->where('active', true)])
            ->orderBy('sort_order')->get()
            ->map(function ($menu) use ($user) {
                $menu->children = $menu->children
                    ->filter(fn($child) => !$child->required_permission || $user->hasPermission($child->required_permission))
                    ->values();
                return $menu;
            })
            ->filter(fn($menu) => (!$menu->required_permission || $user->hasPermission($menu->required_permission)) && ($menu->route || $menu->children->isNotEmpty()))
            ->values();

        return ObjResponse::success($items);
    }
}
