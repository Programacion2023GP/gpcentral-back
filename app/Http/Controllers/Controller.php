<?php

namespace App\Http\Controllers;

use App\Models\ObjResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Controller extends BaseController
{
    // use AuthorizesRequests, ValidatesRequests;

    /**
     * Subir imagen y asignar al modelo.
     *
     * @param Request $request
     * @param string $requestFileName
     * @param string $dirPath
     * @param int|null $id
     * @param string $fileName
     * @param bool $create
     * @param string $fakeName
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return void
     */
    public function ImageUp($request, $requestFileName, $dirPath, $id, $fileName, $create, $fakeName, $model)
    {
        try {
            $dir = public_path($dirPath);
            if ($request->hasFile($requestFileName)) {
                $img_file = $request->file($requestFileName);
                $destination = is_null($id) ? $dir : "$dir/$id";
                $dir_path = is_null($id) ? $dirPath : "$dirPath/$id";
                $img_name = $this->ImgUpload($img_file, $destination, $dir_path, is_null($id) ? $fileName : "$id-$fileName");
                $model->$requestFileName = $img_name;
                $model->save();
            } else {
                if ($create) {
                    $model->$requestFileName = "$dirPath/$fakeName";
                    $model->save();
                }
            }
        } catch (\Exception $ex) {
            Log::error("Controller ~ ImageUp: " . $ex->getMessage());
        }
    }
    /**
     * Funcion para guardar una imagen en directorio fisico, elimina y guarda la nueva al editar la imagen para no guardar muchas
     * imagenes y genera el path que se guardara en la BD
     * 
     * @param $image File es el archivo de la imagen
     * @param $destination String ruta donde se guardara fisicamente el archivo
     * @param $dir String ruta que mandara a la BD
     * @param $imgName String Nombre de como se guardará el archivo fisica y en la BD
     * Subir archivo físicamente.
     *
     * @param \Illuminate\Http\UploadedFile $image
     * @param string $destination
     * @param string $dir
     * @param string $imgName
     * @return string
     */
    public function ImgUpload($image, $destination, $dir, $imgName)
    {
        try {
            $permissions = 0777;
            // Eliminar versiones anteriores con extensiones comunes
            foreach (['PNG', 'JPG', 'JPEG', 'png', 'jpg', 'jpeg'] as $ext) {
                $file = "$dir/$imgName.$ext";
                if (file_exists($file)) {
                    chmod($file, $permissions);
                    @unlink($file);
                }
            }
            $extension = $image->getClientOriginalExtension();
            $fullName = "$imgName.$extension";
            $image->move($destination, $fullName);
            return "$dir/$fullName";
        } catch (\Error $err) {
            Log::error("Controller ~ ImgUpload: " . $err->getMessage());
            return "$dir/noImage.png";
        }
    }



    /**
     * Valida dinámicamente los campos recibidos según las reglas y mensajes personalizados.
     * 
     * @param \Illuminate\Http\Request $request
     * @param string $table Nombre de la tabla a validar.
     * @param array $fields Array de campos a validar, cada campo es un array con 'field', 'label', 'rules', 'messages','validateRequired'.
     * Ejemplo: [
     *     ['field' => 'username', 'label' => 'Nombre de usuario', 'rules' => ['required', 'string'], 'messages' => ['required' => 'El campo username es obligatorio.', 'string' => 'El nombre de usuario debe ser texto.'], 'validateRequired' => true]...
     * @param int|null $id ID del registro a excluir de la validación (para actualizaciones).
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public function validateAvailableData(Request $request, string $table, array $fields, $id = null, bool $validateUniqueFirstField = true)
    {
        $rules = [];
        $messages = [];

        foreach ($fields as $index => $field) {
            $fieldName = $field['field'];
            $label = $field['label'] ?? $fieldName;
            $extraRules = $field['rules'] ?? [];
            $extraMessages = $field['messages'] ?? [];
            $validateRequired = $field['validateRequired'] ?? true;

            $fieldRules = $extraRules;

            if ($validateRequired && !in_array('required', $fieldRules)) {
                array_unshift($fieldRules, 'required');
                $messages["$fieldName.required"] = $extraMessages['required'] ?? "El campo $label es obligatorio.";
            }

            // Unique si es el primer campo y se solicita
            if ($validateUniqueFirstField && $index === 0) {
                $fieldRules[] = "unique:$table,$fieldName," . ($id ?? 'NULL') . ',id,active,1,deleted_at,NULL';
                $messages["$fieldName.unique"] = $extraMessages['unique'] ?? "$label no está disponible! - $request[$fieldName] ya existe.";
            }

            $rules[$fieldName] = $fieldRules;

            foreach ($extraMessages as $rule => $msg) {
                if ($rule !== 'required' && $rule !== 'unique') {
                    $messages["$fieldName.$rule"] = $msg;
                }
            }
        }

        return \Validator::make($request->all(), $rules, $messages);
    }

    /**
     * Funcion para verificar que los datos NO se dupliquen en las tablas correspondientes.
     * 
     * @return ObjRespnse|false
     */
    public function checkAvailableData($table, $column, $value, $propTitle, $input, $id, $secondTable = null)
    {
        $query = "SELECT count(*) as duplicate FROM $table";
        if ($secondTable) {
            $query .= " INNER JOIN $secondTable ON rol_id=rols.id";
        }
        $query .= " WHERE $column='$value' AND active=1";
        if ($id) {
            $query .= " AND id!=$id";
        }
        $result = DB::select($query)[0];
        if ((int)$result->duplicate > 0) {
            return [
                "result" => true,
                "status_code" => 409,
                "alert_icon" => 'warning',
                "alert_title" => "$propTitle no está disponible!",
                "alert_text" => "$propTitle no está disponible! - $value ya existe, intenta con uno diferente.",
                "message" => "duplicate",
                "input" => $input,
                "toast" => false
            ];
        }
        return ["result" => false];
    }

    public function notificationPush($msg, $icon)
    {
        return new StreamedResponse(function () use ($msg, $icon) {
            // Datos que quieres enviar (pueden venir de la base de datos u otro servicio)
            $data = new ObjResponse();
            $data['alert_icon'] = $icon;
            $data['alert_text'] = $msg;
            $data['timestamp'] = now()->toDateTimeString();

            // Envía un evento al cliente
            echo "data: " . json_encode($data) . "\n\n";

            // Forzar el envío del buffer
            ob_flush();
            flush();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
        ]);
    }
}
