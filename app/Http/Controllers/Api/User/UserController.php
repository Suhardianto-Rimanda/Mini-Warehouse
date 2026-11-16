<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        return new UserCollection(User::all());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'  => ['required', 'string', 'min:8', 'max:72', 'confirmed'],
        ]);

        return new UserResource(User::create($data));
    }

    public function show(User $user)
    {
        return new UserResource($user);
    }

    public function update(Request $request, User $user)
    {

        $data = $request->validate([
            'name'              => ['required', 'string', 'max:255'],
            'email'             => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'password'          => ['nullable', 'string', 'min:8', 'max:72', 'confirmed'],
            'current_password'  => ['bail', 'required_with:password', 'string', 'current_password:api'],
        ]);

        unset($data['current_password']);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user = DB::transaction(function () use ($user, $data) {
            $user = User::lockForUpdate()->findOrFail($user->id);
            $user->fill($data);

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }

            $user->save();

            if ($user->wasChanged('password')) {
                $user->tokens()->update(['revoked' => true]);
            }

            return $user;
        }, 3);

        return new UserResource($user);
    }

    public function destroy(Request $request, User $user)
    {
        return DB::transaction(function () use ($user) {
            $user = User::lockForUpdate()->findOrFail($user->id);

            abort_if(
                $user->stockMutations()->exists(),
                409,
                'User has existing stock mutations.'
            );

            $user->tokens()->update(['revoked' => true]);
            $user->delete();

            return response()->json([
                'message' => 'User successfully deleted.',
            ]);
        }, 3);
    }
}
