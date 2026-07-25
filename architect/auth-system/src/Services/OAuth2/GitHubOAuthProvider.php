<?php

namespace Architect\AuthSystem\Services\OAuth2;

/**
 * Провайдер OAuth2 для GitHub.
 */
class GitHubOAuthProvider extends AbstractOAuthProvider
{
    public function getName(): string
    {
        return 'github';
    }

    public function getUserInfo(string $accessToken): array
    {
        try {
            $response = $this->httpClient->get($this->getUserInfoUrl(), [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Accept' => 'application/vnd.github.v3+json',
                ],
            ]);

            $data = json_decode((string) $response->getBody(), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Invalid JSON response from GitHub');
            }

            // Дополнительно получить email, если не включен в основной ответ
            $email = $data['email'] ?? null;
            if (empty($email)) {
                $email = $this->fetchPrimaryEmail($accessToken);
            }

            return [
                'id' => $data['id'] ?? null,
                'login' => $data['login'] ?? null,
                'email' => $email,
                'name' => $data['name'] ?? null,
                'avatar_url' => $data['avatar_url'] ?? null,
                'url' => $data['html_url'] ?? null,
                'company' => $data['company'] ?? null,
                'location' => $data['location'] ?? null,
                'bio' => $data['bio'] ?? null,
                'public_repos' => $data['public_repos'] ?? null,
                'followers' => $data['followers'] ?? null,
                'following' => $data['following'] ?? null,
                'created_at' => $data['created_at'] ?? null,
                'updated_at' => $data['updated_at'] ?? null,
            ];
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            throw new \RuntimeException('Failed to fetch user info from GitHub: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Получить основной email пользователя.
     */
    private function fetchPrimaryEmail(string $accessToken): ?string
    {
        try {
            $response = $this->httpClient->get('https://api.github.com/user/emails', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Accept' => 'application/vnd.github.v3+json',
                ],
            ]);

            $emails = json_decode((string) $response->getBody(), true);
            foreach ($emails as $email) {
                if ($email['primary'] ?? false) {
                    return $email['email'];
                }
            }
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            // Игнорируем ошибку, вернём null
        }

        return null;
    }

    protected function getBaseAuthorizationUrl(): string
    {
        return 'https://github.com/login/oauth/authorize';
    }

    protected function getTokenUrl(): string
    {
        return 'https://github.com/login/oauth/access_token';
    }

    protected function getValidationUrl(): string
    {
        return 'https://api.github.com/user';
    }

    protected function getUserInfoUrl(): string
    {
        return 'https://api.github.com/user';
    }

    protected function getDefaultScopes(): array
    {
        return [
            'read:user',
            'user:email',
        ];
    }
}
