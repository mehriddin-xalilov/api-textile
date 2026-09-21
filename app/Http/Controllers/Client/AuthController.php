<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
use App\Http\Requests\Client\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Auth\PassportTokenService;
use App\Support\ApiResponse;
use App\Support\Phone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function __construct(private readonly PassportTokenService $tokens) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::query()->create($request->validated());
        $user->assignRole('customer');

        $tokens = $this->tokens->issueByPassword($user->phone_number, $request->password);

        return ApiResponse::created(['user' => new UserResource($user->load('avatar'))] + $tokens);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $tokens = $this->tokens->issueByPassword($request->login, $request->password);
        $user = User::query()->with('avatar')->firstWhere('phone_number', Phone::normalize($request->login));
        $user?->forceFill(['last_login_at' => now()])->saveQuietly();

        return ApiResponse::item(['user' => new UserResource($user)] + $tokens);
    }

    public function refresh(Request $request): JsonResponse
    {
        $request->validate(['refresh_token' => ['required', 'string']]);

        return ApiResponse::item($this->tokens->refresh($request->refresh_token));
    }

    public function me(Request $request): JsonResponse
    {
        return ApiResponse::item(new UserResource($request->user()->load('avatar')));
    }

    /**
     * Telefon raqam bazada bormi — kirish oynasi bir bosqichli bo'lishi uchun.
     * Parol so'ralmaydi, shuning uchun throttle bilan cheklangan.
     */
    public function check(Request $request): JsonResponse
    {
        $data = $request->validate(['phone_number' => ['required', 'string', 'max:20']]);
        $phone = Phone::normalize($data['phone_number']);

        return ApiResponse::item([
            'exists' => User::query()->where('phone_number', $phone)->exists(),
            'phone_number' => $phone,
        ]);
    }

    /** Profil ma'lumotlarini yangilash (ism, familiya, email). */
    public function updateProfile(Request $request): JsonResponse
    {
        $data = $request->validate([
            'first_name' => ['sometimes', 'required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($request->user()->id)],
        ]);

        $user = $request->user();
        $user->update($data);

        return ApiResponse::item(new UserResource($user->fresh()));
    }

    public function logout(Request $request): JsonResponse
    {
        $this->tokens->revokeCurrent($request->user());

        return ApiResponse::message('Chiqildi');
    }
}
