<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Support\Phone;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Passport\AccessToken;
use Laravel\Passport\RefreshToken;

/**
 * Passport password grant ustidan yupqa qatlam.
 * /oauth/token ga ichki so'rov yuboradi — client secret frontendga chiqmaydi.
 */
final class PassportTokenService
{
    /** @return array{token: string, refresh_token: string, expires_in: int} */
    public function issueByPassword(string $login, string $password): array
    {
        return $this->request([
            'grant_type' => 'password',
            'username' => Phone::normalize($login),
            'password' => $password,
            'scope' => '*',
        ], 'login');
    }

    /** @return array{token: string, refresh_token: string, expires_in: int} */
    public function refresh(string $refreshToken): array
    {
        return $this->request([
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'scope' => '*',
        ], 'refresh_token');
    }

    public function revokeCurrent(User $user): void
    {
        $token = $user->currentAccessToken();

        if (! $token instanceof AccessToken) {
            return;
        }

        RefreshToken::query()->where('access_token_id', $token->oauth_access_token_id)->update(['revoked' => true]);
        $token->revoke();
    }

    private function request(array $params, string $errorField): array
    {
        $request = Request::create('/oauth/token', 'POST', $params + [
            'client_id' => config('passport.password_client_id'),
            'client_secret' => config('passport.password_client_secret'),
        ]);

        // Passport ServerRequestInterface ni konteynerdagi 'request' dan oladi,
        // shuning uchun ichki so'rovni to'liq kernel orqali o'tkazamiz va asl request'ni tiklaymiz.
        $original = app('request');
        try {
            $response = app()->handle($request);
        } finally {
            app()->instance('request', $original);
        }

        $payload = json_decode($response->getContent(), true) ?: [];

        if ($response->getStatusCode() !== 200) {
            throw ValidationException::withMessages([
                $errorField => [$payload['message'] ?? __('auth.failed')],
            ]);
        }

        return [
            'token' => $payload['access_token'],
            'refresh_token' => $payload['refresh_token'],
            'expires_in' => $payload['expires_in'],
        ];
    }
}
