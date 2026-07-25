<?php

namespace Architect\AuthSystem\Services\OAuth2;

/**
 * Провайдер OAuth2 для Google.
 */
class GoogleOAuthProvider extends AbstractOAuthProvider
{
    public function getName(): string
    {
        return 'google';
    }

    public function getUserInfo(string $accessToken): array
    {
        try {
            $response = $this->httpClient->get($this->getUserInfoUrl(), [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
            ]);

            $data = json_decode((string) $response->getBody(), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Invalid JSON response from Google');
            }

            return [
                'id' => $data['sub'] ?? null,
                'email' => $data['email'] ?? null,
                'verified_email' => $data['email_verified'] ?? false,
                'name' => $data['name'] ?? null,
                'given_name' => $data['given_name'] ?? null,
                'family_name' => $data['family_name'] ?? null,
                'picture' => $data['picture'] ?? null,
                'locale' => $data['locale'] ?? null,
                'hd' => $data['hd'] ?? null, // домен Google Workspace
            ];
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            throw new \RuntimeException('Failed to fetch user info from Google: ' . $e->getMessage(), 0, $e);
        }
    }

    protected function getBaseAuthorizationUrl(): string
    {
        return 'https://accounts.google.com/o/oauth2/v2/auth';
    }

    protected function getTokenUrl(): string
    {
        return 'https://oauth2.googleapis.com/token';
    }

    protected function getValidationUrl(): string
    {
        return 'https://oauth2.googleapis.com/tokeninfo';
    }

    protected function getUserInfoUrl(): string
    {
        return 'https://openidconnect.googleapis.com/v1/userinfo';
    }

    protected function getDefaultScopes(): array
    {
        return [
            'openid',
            'email',
            'profile',
        ];
    }
}
