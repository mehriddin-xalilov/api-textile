<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Auth\PassportTokenService;
use App\Support\ApiResponse;
use App\Support\Phone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private readonly PassportTokenService $tokens) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $tokens = $this->tokens->issueByPassword($request->login, $request->password);

        $user = User::query()
            ->with(['roles.permissions', 'permissions', 'avatar'])
            ->firstWhere(fn ($q) => $q
                ->where('phone_number', Phone::normalize($request->login))
                ->orWhere('email', $request->login));

        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        return ApiResponse::item(['user' => new UserResource($user)] + $tokens);
    }

    public function refresh(Request $request): JsonResponse
    {
        $request->validate(['refresh_token' => ['required', 'string']]);

        return ApiResponse::item($this->tokens->refresh($request->refresh_token));
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['roles.permissions', 'permissions', 'avatar']);

        return ApiResponse::item(new UserResource($user));
    }

    public function logout(Request $request): JsonResponse
    {
        $this->tokens->revokeCurrent($request->user());

        return ApiResponse::message('Chiqildi');
    }
}
