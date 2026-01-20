<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Listar todos los usuarios
     * 
     * @param Request $request
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Verificar que sea admin
        if (!$user->hasRole('admin')) {
            abort(403, 'No tienes permisos de administrador');
        }
        
        $search = $request->query('search', '');
        $roleFilter = $request->query('role', '');
        
        $query = User::with('roles');
        
        if ($search) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('unique_code', 'like', "%{$search}%");
        }
        
        if ($roleFilter) {
            $query->whereHas('roles', function ($q) use ($roleFilter) {
                $q->where('name', $roleFilter);
            });
        }
        
        $users = $query->orderByDesc('created_at')
            ->paginate(20)
            ->through(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'unique_code' => $user->unique_code,
                    'is_active' => $user->is_active,
                    'roles' => $user->roles->pluck('name')->toArray(),
                    'last_login' => $user->last_login,
                    'created_at' => $user->created_at,
                ];
            });
        
        // Obtener todos los roles disponibles
        $roles = Role::all()->pluck('name')->toArray();
        
        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'roles' => $roles,
            'filters' => [
                'search' => $search,
                'role' => $roleFilter,
            ],
        ]);
    }

    /**
     * Crear nuevo usuario
     * 
     * @param Request $request
     * @return \Inertia\Response
     */
    public function create(Request $request)
    {
        $user = $request->user();
        
        if (!$user->hasRole('admin')) {
            abort(403);
        }
        
        $roles = Role::all();
        
        return Inertia::render('Admin/Users/Create', [
            'roles' => $roles,
        ]);
    }

    /**
     * Guardar nuevo usuario
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $user = $request->user();
        
        if (!$user->hasRole('admin')) {
            abort(403);
        }
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'roles' => 'required|array|min:1|max:10',
            'roles.*' => 'integer|exists:roles,id',
            'is_active' => 'boolean',
        ], [
            'roles.required' => 'Debes seleccionar al menos un rol',
            'roles.min' => 'Debes seleccionar al menos un rol',
            'roles.*.exists' => 'Uno de los roles seleccionados no existe',
        ]);
        
        $newUser = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'unique_code' => 'USR_' . time() . '_' . uniqid(),
            'is_active' => $validated['is_active'] ?? true,
        ]);
        
        // Asignar roles
        if (!empty($validated['roles'])) {
            $newUser->roles()->attach($validated['roles']);
        }
        
        return redirect()->route('admin.users.index')
            ->with('message', 'Usuario creado exitosamente');
    }

    /**
     * Editar usuario
     * 
     * @param Request $request
     * @param User $user
     * @return \Inertia\Response
     */
    public function edit(Request $request, User $user)
    {
        $authUser = $request->user();
        
        if (!$authUser->hasRole('admin')) {
            abort(403);
        }
        
        $roles = Role::all();
        
        return Inertia::render('Admin/Users/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'unique_code' => $user->unique_code,
                'is_active' => $user->is_active,
                'roles' => $user->roles->pluck('id')->toArray(),
                'created_at' => $user->created_at,
                'last_login' => $user->last_login,
            ],
            'roles' => $roles,
        ]);
    }

    /**
     * Actualizar usuario
     * 
     * @param Request $request
     * @param User $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, User $user)
    {
        $authUser = $request->user();
        
        if (!$authUser->hasRole('admin')) {
            abort(403);
        }
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:roles,id',
            'is_active' => 'boolean',
        ]);
        
        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'is_active' => $validated['is_active'] ?? $user->is_active,
        ]);
        
        if ($validated['password']) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }
        
        // Actualizar roles
        $user->roles()->sync($validated['roles']);
        
        return redirect()->route('admin.users.index')
            ->with('message', 'Usuario actualizado exitosamente');
    }

    /**
     * Eliminar usuario
     * 
     * @param Request $request
     * @param User $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request, User $user)
    {
        $authUser = $request->user();
        
        if (!$authUser->hasRole('admin')) {
            abort(403);
        }
        
        // No permitir eliminar el mismo usuario
        if ($user->id === $authUser->id) {
            return back()->withErrors(['error' => 'No puedes eliminar tu propio usuario']);
        }
        
        $user->roles()->detach();
        $user->delete();
        
        return redirect()->route('admin.users.index')
            ->with('message', 'Usuario eliminado exitosamente');
    }

    /**
     * Cambiar estado del usuario
     * 
     * @param Request $request
     * @param User $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function toggleStatus(Request $request, User $user)
    {
        $authUser = $request->user();
        
        if (!$authUser->hasRole('admin')) {
            abort(403);
        }
        
        $user->update(['is_active' => !$user->is_active]);
        
        return back()->with('message', 'Estado del usuario actualizado');
    }
}
