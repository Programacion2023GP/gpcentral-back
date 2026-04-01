<?php

namespace App\Http\Controllers;

use App\Models\ObjResponse;
use App\Models\User;
use App\Models\VW_User; // Asumiendo que existe esta vista
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
   /**
    * Iniciar sesión.
    */
   public function login(Request $request)
   {
      try {
         $field = 'username';
         $value = $request->username;
         if ($request->email) {
            $field = 'email';
            $value = $request->email;
         } elseif ($request->payroll_number) {
            $field = 'payroll_number';
            $value = $request->payroll_number;
         }

         $request->validate([
            $field => 'required',
            'password' => 'required'
         ]);

         // Buscar en vista VW_User (que debe incluir datos de empleado)
         $user = VW_User::where($field, $value)
            ->where('active', 1)
            ->orderBy('id', 'desc')
            ->first();

         if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
               'message' => 'Credenciales incorrectas'
            ]);
         }

         $token = $user->createToken($user->email, [$user->role])->plainTextToken;

         return ObjResponse::success([
            'token' => $token,
            'auth' => $user
         ], 'Usuario logeado');
      } catch (ValidationException $ex) {
         return ObjResponse::error('Credenciales incorrectas', 401, 'Error de autenticación');
      } catch (\Exception $ex) {
         Log::error("AuthController ~ login: " . $ex->getMessage());
         return ObjResponse::serverError('Error al iniciar sesión', $ex);
      }
   }

   /**
    * Cerrar sesión.
    */
   public function logout(Request $request)
   {
      try {
         $allSessions = $request->boolean('all_sessions', false);
         if (!$allSessions) {
            Auth::user()->currentAccessToken()->delete();
         } else {
            Auth::user()->tokens()->delete();
         }
         return ObjResponse::success(null, 'Sesión cerrada');
      } catch (\Exception $ex) {
         Log::error("AuthController ~ logout: " . $ex->getMessage());
         return ObjResponse::serverError('Error al cerrar sesión', $ex);
      }
   }

   /**
    * Registro de nuevo usuario (ciudadano) con vinculación a empleado.
    * Si se proporciona employee_code, se vincula al empleado existente.
    */
   public function signup(Request $request)
   {
      try {
         $validator = $this->validateAvailableData($request, 'users', [
            [
               'field' => 'username',
               'label' => 'Nombre de usuario',
               'rules' => ['string', 'unique:users,username'],
            ],
            [
               'field' => 'email',
               'label' => 'Correo electrónico',
               'rules' => ['email', 'unique:users,email'],
            ],
            [
               'field' => 'password',
               'label' => 'Contraseña',
               'rules' => ['string', 'min:6', 'confirmed'],
            ],
            [
               'field' => 'employee_code',
               'label' => 'Código de empleado',
               'rules' => ['nullable', 'exists:employees,employee_code'],
               'validateRequired' => false,
            ]
         ]);

         if ($validator->fails()) {
            return ObjResponse::validationError($validator->errors()->toArray());
         }

         $data = [
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            // 'role_id' => 6, // Rol ciudadano
         ];

         if ($request->filled('employee_code')) {
            $employee = Employee::where('employee_code', $request->employee_code)->first();
            if ($employee) {
               $data['employee_id'] = $employee->id;
            }
         }

         $user = User::create($data);

         return ObjResponse::success($user, 'Usuario registrado');
      } catch (\Exception $ex) {
         Log::error("AuthController ~ signup: " . $ex->getMessage());
         return ObjResponse::serverError('Error al registrar usuario', $ex);
      }
   }

   /**
    * Cambiar contraseña del usuario autenticado.
    */
   public function changePasswordAuth(Request $request)
   {
      try {
         $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|string|min:6|confirmed',
         ]);

         $user = Auth::user();

         if (!Hash::check($request->current_password, $user->password)) {
            return ObjResponse::error('La contraseña actual no es correcta', 400);
         }

         $user->password = Hash::make($request->new_password);
         $user->save();

         // Revocar todos los tokens
         $user->tokens()->delete();

         return ObjResponse::success(null, 'Contraseña actualizada. Todas las sesiones se cerraron.');
      } catch (\Exception $ex) {
         Log::error("AuthController ~ changePasswordAuth: " . $ex->getMessage());
         return ObjResponse::serverError('Error al cambiar contraseña', $ex);
      }
   }

   /**
    * Obtener usuario autenticado.
    */
   public function me()
   {
      try {
         $user = Auth::user()->load('employee', 'role');
         return ObjResponse::success($user);
      } catch (\Exception $ex) {
         Log::error("AuthController ~ me: " . $ex->getMessage());
         return ObjResponse::serverError('Error al obtener usuario', $ex);
      }
   }
}