<?php

namespace App\Http\Controllers;

use App\Models\ObjResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

abstract class BaseCrudController extends Controller
{
   /**
    * Modelo principal (ej. Employee::class)
    * @var string
    */
   protected $modelClass;

   /**
    * Modelo si tiene vistas (ej. VW_Employee::class)
    * @var string
    */
   protected $modelClassView = null;

   /**
    * Recurso API (opcional, para transformar respuestas)
    * @var string|null
    */
   protected $resourceClass = null;

   /**
    * Reglas de validación para crear/actualizar.
    * Formato: ['campo' => 'reglas|separadas|por|pipe', ...]
    * @var array
    */
   protected $validationRules = [];

   /**
    * Mensajes personalizados de validación.
    * @var array
    */
   protected $validationMessages = [];

   /**
    * Campos de imagen a procesar con ImageUp.
    * @var array
    */
   protected $imageFields = [];

   /**
    * Directorio base para imágenes (relativo a public_path).
    * @var string
    */
   protected $imageDirectory = '';

   /**
    * Orden por defecto para index (campo => dirección).
    * @var array
    */
   protected $defaultOrderBy = ['id' => 'desc'];

   /**
    * Indica si se debe filtrar por active según el rol del usuario.
    * @var bool
    */
   protected $useAuthFilter = true;

   /**
    * Indica si se debe filtrar role y mostrar del mismo rol e inferiores.
    * @var bool
    */
   protected $filterByRoleAuth = false;

   /**
    * Indica si el modelo usa versionado (uuid + start_date/end_date).
    * @var bool
    */
   protected $versioned = false;

   /**
    * Callback para modificar la consulta de index.
    * @var callable|null
    */
   protected $indexQueryCallback = null;

   /**
    * Callback para modificar la consulta de selectIndex.
    * @var callable|null
    */
   protected $selectIndexQueryCallback = null;

   /**
    * Configuración para el campo 'id' en selectIndex.
    * Puede ser un string (nombre de columna) o un array con una expresión DB::raw.
    * @var string|array
    */
   protected $selectId = 'id';

   /**
    * Configuración para el campo 'label' en selectIndex.
    * Puede ser un string (nombre de columna) o un array con una expresión DB::raw.
    * @var string|array
    */
   protected $selectLabel = 'name';

   /**
    * Mostrar lista paginada.
    */
   public function index(Request $request): JsonResponse
   {
      try {
         $query = $this->modelClassView ? $this->modelClassView::query() : $this->modelClass::query();

         // Filtro por autenticación
         if ($this->useAuthFilter) {
            $auth = Auth::user();

            if (Schema::hasColumn($this->modelClassView->getTable(), 'uuid')) {
               // La columna existe en la base de datos
               $query->whereNull('end_date');
            }
            if ($auth && isset($auth->role_id) && $auth->role_id > 2) {
               $query->where('active', true);
            }
         }

         // Filtrar que traiga solo del tipo de rol e inferiores
         if ($this->filterByRoleAuth && Auth::user()) {
            $roleAuth = Auth::user()->role_id;
            $query->where("role_id", ">=", $roleAuth);
         }

         // Orden por defecto
         foreach ($this->defaultOrderBy as $field => $direction) {
            $query->orderBy($field, $direction);
         }

         // Callback personalizado
         if ($this->indexQueryCallback) {
            call_user_func($this->indexQueryCallback, $query, $request);
         }

         // $perPage = $request->get('per_page', 15);
         // $list = $query->paginate($perPage);
         $list = isset($request->per_page) ? $query->paginate($request->per_page ?? 100) : $query->get();

         $data = $this->resourceClass
            ? $this->resourceClass::collection($list)
            : $list;

         return ObjResponse::success($data, 'Petición satisfactoria | Lista.');
      } catch (\Exception $ex) {
         $msg = get_class($this) . " ~ index ~ " . $ex->getMessage();
         Log::error($msg);
         return ObjResponse::serverError('Error al obtener lista', $ex);
      }
   }

   /**
    * Lista para selectores (id, label).
    */
   public function selectIndex(Request $request)
   {
      try {
         $query = $this->modelClassView ? $this->modelClassView::where('active', true) : $this->modelClass::where('active', true);


         if (is_array($this->selectLabel)) {
            $labelField = DB::raw($this->selectLabel[0]);
         } else {
            $labelField = $this->selectLabel;
         }
         $query->select("{$this->selectId} as id", DB::raw("{$labelField} as label"));

         if ($this->selectIndexQueryCallback) {
            call_user_func($this->selectIndexQueryCallback, $query, $request);
         }

         $query->orderBy('label', 'asc');
         $list = $query->get();

         return ObjResponse::success($list, 'Lista select obtenida');
      } catch (\Exception $ex) {
         Log::error(get_class($this) . " ~ selectIndex: " . $ex->getMessage());
         return ObjResponse::serverError('Error al obtener lista select', $ex);
      }
   }

   /**
    * Crear o actualizar registro.
    */
   public function createOrUpdate(Request $request)
   {
      try {
         $id = $request->id ?? null;

         $validator = $this->validateRequest($request, $id);
         if ($validator->fails()) {
            return ObjResponse::validationError($validator->errors()->toArray());
         }

         if ($this->versioned) {
            return $this->versionedCreateOrUpdate($request, $id);
         }

         $model = $id ? $this->modelClass::find($id) : new $this->modelClass;

         $fillData = $request->except($this->imageFields);
         $model->fill($fillData);
         $model->save();

         foreach ($this->imageFields as $field) {
            $this->ImageUp(
               $request,
               $field,
               $this->imageDirectory,
               $versioned ? $model->uuid : $model->id,
               strtoupper($field),
               is_null($id),
               "noImage.png",
               $model
            );
         }

         $message = $id ? 'Registro actualizado' : 'Registro creado';
         return ObjResponse::success($model, $message);
      } catch (\Exception $ex) {
         Log::error(get_class($this) . " ~ createOrUpdate: " . $ex->getMessage());
         return ObjResponse::serverError('Error al guardar', $ex);
      }
   }

   /**
    * Versión concreta para modelos versionados (uuid + start_date/end_date).
    * Cierra la versión actual y crea una nueva con el mismo uuid.
    */
   protected function versionedCreateOrUpdate(Request $request, $id)
   {
      if ($id) {
         $current = $this->modelClass::where('id', $id)->whereNull('end_date')->first();
         if (!$current) {
            return ObjResponse::notFound('Versión actual no encontrada');
         }

         $effectiveDate = $request->get('start_date', now()->toDateString());

         $current->end_date = $effectiveDate;
         $current->active = false;
         $current->save();

         $data = $request->except($this->imageFields);
         $data['uuid'] = $current->uuid;
         $data['start_date'] = $effectiveDate;
         $data['end_date'] = null;
         $data['active'] = true;
         $new = $this->modelClass::create($data);
      } else {
         $data = $request->except($this->imageFields);
         $data['start_date'] = $request->get('start_date', now()->toDateString());
         $data['active'] = true;
         $new = $this->modelClass::create($data);
      }

      foreach ($this->imageFields as $field) {
         $this->ImageUp(
            $request,
            $field,
            $this->imageDirectory,
            $versioned ? $new->uuid : $new->id,
            strtoupper($field),
            is_null($id),
            "noImage.png",
            $new
         );
      }

      $message = $id ? 'Registro actualizado' : 'Registro creado';
      return ObjResponse::success($new, $message);
   }

   /**
    * Mostrar un registro.
    */
   public function show($id, Request $request, $internal = false)
   {
      try {
         $model = $this->modelClass::find($id);
         if (!$model) {
            if ($internal) return null;
            return ObjResponse::notFound('Registro no encontrado');
         }
         if ($internal) return $model;
         $data = $this->resourceClass ? new $this->resourceClass($model) : $model;
         return ObjResponse::success($data, 'Registro encontrado');
      } catch (\Exception $ex) {
         Log::error(get_class($this) . " ~ show ~ " . $ex->getMessage());
         return ObjResponse::serverError('Error al obtener registro', $ex);
      }
   }

   /**
    * Eliminar un registro.
    */
   public function delete(Request $request)
   {
      try {
         $model = $this->modelClass::find($request->id);
         if (!$model) {
            return ObjResponse::notFound('Registro no encontrado');
         }
         $model->update(['active' => false, 'deleted_at' => now()]);
         return ObjResponse::success(null, 'Registro eliminado');
      } catch (\Exception $ex) {
         Log::error(get_class($this) . " ~ destroy ~ " . $ex->getMessage());
         return ObjResponse::serverError('Error al eliminar', $ex);
      }
   }

   /**
    * Activar o desactivar registro.
    */
   public function disEnable(Response $response, $id, $action)
   {
      try {
         $model = $this->modelClass::find($id);
         if (!$model) {
            return ObjResponse::notFound('Registro no encontrado');
         }
         $active = ($action === 'reactivar') ? 1 : 0;
         $model->update(['active' => $active]);
         $description = $active ? 'reactivado' : 'desactivado';
         return ObjResponse::success(null, "Registro $description");
      } catch (\Exception $ex) {
         Log::error(get_class($this) . " ~ disEnable ~ " . $ex->getMessage());
         return ObjResponse::serverError('Error al cambiar estado', $ex);
      }
   }

   /**
    * Eliminar múltiples registros.
    */
   public function deleteMultiple(Request $request)
   {
      try {
         $ids = $request->ids;
         if (!is_array($ids)) {
            $ids = explode(',', $ids);
         }
         $countDeleted = count($ids);
         $this->modelClass::whereIn('id', $ids)->update([
            'active' => false,
            'deleted_at' => now()
         ]);
         $message = $countDeleted == 1 ? 'Registro eliminado' : "Registros eliminados ($countDeleted)";
         return ObjResponse::success(null, $message);
      } catch (\Exception $ex) {
         Log::error(get_class($this) . " ~ deleteMultiple ~ " . $ex->getMessage());
         return ObjResponse::serverError('Error al eliminar múltiples', $ex);
      }
   }

   /**
    * Valida la petición usando el validador de Laravel.
    * Maneja automáticamente la regla 'unique' para ignorar el ID actual.
    */
   protected function validateRequest(Request $request, $id = null)
   {
      $rules = $this->validationRules;
      $table = (new $this->modelClass)->getTable();

      foreach ($rules as $field => $rule) {
         if (is_string($rule) && str_contains($rule, 'unique:')) {
            $rules[$field] = \Illuminate\Validation\Rule::unique($table, $field)->ignore($id);
         } elseif (is_array($rule)) {
            foreach ($rule as $key => $subRule) {
               if (is_string($subRule) && str_contains($subRule, 'unique:')) {
                  $rule[$key] = \Illuminate\Validation\Rule::unique($table, $field)->ignore($id);
               }
            }
            $rules[$field] = $rule;
         }
      }

      return Validator::make($request->all(), $rules, $this->validationMessages);
   }
}
