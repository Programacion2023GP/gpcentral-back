<?php

namespace App\Models;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Clase para construir respuestas JSON consistentes en toda la API.
 */
class ObjResponse
{
    /**
     * Estructura base de la respuesta.
     *
     * @var array
     */
    protected $structure = [
        'status_code' => 200,
        'status' => true,
        'message' => '',
        'alert_icon' => 'success',
        'alert_title' => 'Éxito',
        'alert_text' => '',
        'result' => [],
        'toast' => true,
    ];

    /**
     * Constructor privado para evitar instanciación directa (si se usan métodos estáticos).
     */
    private function __construct() {}

    // ==================== MÉTODOS ESTÁTICOS DE CREACIÓN ====================

    /**
     * Crea una respuesta de éxito.
     *
     * @param mixed $data
     * @param string $message
     * @param string $title
     * @return JsonResponse
     */
    public static function success($data = [], string $message = 'Petición satisfactoria', string $title = 'Éxito'): JsonResponse
    {
        return self::build([
            'status_code' => 200,
            'status' => true,
            'message' => $message,
            'alert_icon' => 'success',
            'alert_title' => $title,
            'alert_text' => $message,
            'result' => $data,
            'toast' => true,
        ]);
    }

    /**
     * Crea una respuesta de error general.
     *
     * @param string $message
     * @param int $statusCode
     * @param string $title
     * @return JsonResponse
     */
    public static function error(string $message, int $statusCode = 400, string $title = 'Error'): JsonResponse
    {
        return self::build([
            'status_code' => $statusCode,
            'status' => false,
            'message' => $message,
            'alert_icon' => 'error',
            'alert_title' => $title,
            'alert_text' => $message,
            'result' => [],
            'toast' => false,
        ]);
    }

    /**
     * Respuesta para recurso no encontrado (404).
     *
     * @param string $message
     * @return JsonResponse
     */
    public static function notFound(string $message = 'Recurso no encontrado'): JsonResponse
    {
        return self::error($message, 404, 'No encontrado');
    }

    /**
     * Respuesta para error de validación (422).
     *
     * @param array $errors
     * @param string $message
     * @return JsonResponse
     */
    public static function validationError(array $errors, string $message = 'Error de validación'): JsonResponse
    {
        return self::build([
            'status_code' => 422,
            'status' => false,
            'message' => $message,
            'alert_icon' => 'warning',
            'alert_title' => 'Datos inválidos',
            'alert_text' => $message,
            'errors' => $errors, // Campo adicional para detalles
            'result' => [],
            'toast' => false,
        ]);
    }

    /**
     * Respuesta para cuando el usuario no está autorizado (403).
     *
     * @param string $message
     * @return JsonResponse
     */
    public static function forbidden(string $message = 'No tienes permiso para realizar esta acción'): JsonResponse
    {
        return self::error($message, 403, 'Acceso denegado');
    }

    /**
     * Respuesta para cuando el usuario no está autenticado (401).
     *
     * @param string $message
     * @return JsonResponse
     */
    public static function unauthorized(string $message = 'No autenticado'): JsonResponse
    {
        return self::error($message, 401, 'Autenticación requerida');
    }

    /**
     * Respuesta para errores internos del servidor (500).
     *
     * @param string $message
     * @param \Throwable|null $exception (opcional, para loguear)
     * @return JsonResponse
     */
    public static function serverError(string $message = 'Error interno del servidor', ?\Throwable $exception = null): JsonResponse
    {
        if ($exception) {
            Log::error($exception->getMessage(), ['exception' => $exception]);
        }

        return self::build([
            'status_code' => 500,
            'status' => false,
            'message' => $message,
            'alert_icon' => 'error',
            'alert_title' => 'Error crítico',
            'alert_text' => 'Ocurrió un problema en el servidor. Intente más tarde.',
            'result' => [],
            'toast' => false,
        ]);
    }

    /**
     * Respuesta por defecto (útil como punto de partida).
     *
     * @return JsonResponse
     */
    public static function default(): JsonResponse
    {
        return self::build([
            'status_code' => 500,
            'status' => false,
            'message' => 'No se logró completar la petición.',
            'alert_icon' => 'informative',
            'alert_title' => 'Lo sentimos',
            'alert_text' => 'Hay un problema con el servidor. Intente más tarde.',
            'result' => [],
            'toast' => false,
        ]);
    }

    /**
     * Método interno para construir la respuesta JSON.
     *
     * @param array $data
     * @return JsonResponse
     */
    private static function build(array $data): JsonResponse
    {
        return response()->json($data, $data['status_code']);
    }

    // ==================== MÉTODOS DE INSTANCIA (FLUENT) ====================
    // Si prefieres usar instancias en lugar de métodos estáticos, puedes implementar:

    // public static function make(): self
    // {
    //     return new self;
    // }

    // public function setStatusCode(int $code): self { ... }
    // public function setMessage(string $message): self { ... }
    // public function setData($data): self { ... }
    // public function send(): JsonResponse { ... }
}
