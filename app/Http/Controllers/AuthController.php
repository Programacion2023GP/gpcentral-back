<?php

namespace App\Http\Controllers;

use App\Models\ObjResponse;
use App\Models\User;
use App\Models\Employee;
use App\Models\VW_User; // Asumiendo que existe esta vista
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

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

         $query = User::with(['employee.currentDetail', 'employee.currentAssignment', 'role.permissions'])
            ->where('active', true);
         if ($field === 'payroll_number') {
            $query->whereHas('employee', fn($employee) => $employee->where('employee_code', $value));
         } else {
            $query->where($field, $value);
         }
         $user = $query->first();

         if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
               'message' => 'Credenciales incorrectas'
            ]);
         }

         if (!$user->role || !$user->role->active) {
            return ObjResponse::error('El usuario no tiene un perfil activo', 403);
         }

         $permissions = $user->role->slug === 'superadmin'
            ? ['*']
            : $user->role->permissions->pluck('code')->values()->all();
         $token = $user->createToken($user->email, $permissions)->plainTextToken;

         return ObjResponse::success([
            'token' => $token,
            'auth' => $user,
            'permissions' => $permissions,
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
         $user = Auth::user()->load('employee.currentDetail', 'employee.currentAssignment', 'role.permissions');
         return ObjResponse::success($user);
      } catch (\Exception $ex) {
         Log::error("AuthController ~ me: " . $ex->getMessage());
         return ObjResponse::serverError('Error al obtener usuario', $ex);
      }
   }

   public function forgotPassword(Request $request)
   {
      $request->validate(['email' => 'required|email']);
      Password::sendResetLink($request->only('email'));
      return ObjResponse::success(null, 'Si el correo está registrado, recibirás instrucciones para restablecer tu contraseña.');
   }

   public function resetPassword(Request $request)
   {
      $request->validate([
         'token' => 'required',
         'email' => 'required|email',
         'password' => 'required|string|min:8|confirmed',
      ]);

      $status = Password::reset(
         $request->only('email', 'password', 'password_confirmation', 'token'),
         function (User $user, string $password) {
            $user->forceFill([
               'password' => Hash::make($password),
               'remember_token' => Str::random(60),
            ])->save();
            $user->tokens()->delete();
         }
      );

      return $status === Password::PASSWORD_RESET
         ? ObjResponse::success(null, 'Contraseña actualizada')
         : ObjResponse::error('El enlace es inválido o expiró', 422);
   }
}
