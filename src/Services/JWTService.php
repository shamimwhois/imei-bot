<?php

declare(strict_types=1);

namespace IMEIBot\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\BeforeValidException;
use IMEIBot\Core\Logger;

class JWTService
{
    private string $secretKey;
    private string $algorithm;
    private int $tokenExpiration;
    private int $refreshTokenExpiration;
    private Logger $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
        $this->secretKey = $_ENV['JWT_SECRET'] ?? $this->generateSecretKey();
        $this->algorithm = $_ENV['JWT_ALGORITHM'] ?? 'HS256';
        $this->tokenExpiration = (int) ($_ENV['JWT_TOKEN_EXPIRATION'] ?? 3600); // 1 hour
        $this->refreshTokenExpiration = (int) ($_ENV['JWT_REFRESH_TOKEN_EXPIRATION'] ?? 604800); // 7 days
    }

    public function generateToken(array $payload): array
    {
        $now = time();
        
        $tokenPayload = array_merge($payload, [
            'iat' => $now,
            'exp' => $now + $this->tokenExpiration,
            'type' => 'access'
        ]);

        $refreshPayload = array_merge($payload, [
            'iat' => $now,
            'exp' => $now + $this->refreshTokenExpiration,
            'type' => 'refresh'
        ]);

        try {
            $accessToken = JWT::encode($tokenPayload, $this->secretKey, $this->algorithm);
            $refreshToken = JWT::encode($refreshPayload, $this->secretKey, $this->algorithm);

            $this->logger->info('JWT tokens generated', [
                'user_id' => $payload['user_id'] ?? null,
                'role' => $payload['role'] ?? 'unknown'
            ]);

            return [
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'token_type' => 'Bearer',
                'expires_in' => $this->tokenExpiration,
                'refresh_expires_in' => $this->refreshTokenExpiration
            ];
        } catch (\Exception $e) {
            $this->logger->error('JWT token generation failed', [
                'error' => $e->getMessage(),
                'payload' => $payload
            ]);
            throw new \RuntimeException('Failed to generate JWT tokens: ' . $e->getMessage());
        }
    }

    public function validateToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, $this->algorithm));
            return (array) $decoded;
        } catch (ExpiredException $e) {
            $this->logger->warning('JWT token expired', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Token has expired');
        } catch (SignatureInvalidException $e) {
            $this->logger->warning('JWT token signature invalid', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Invalid token signature');
        } catch (BeforeValidException $e) {
            $this->logger->warning('JWT token not yet valid', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Token is not yet valid');
        } catch (\Exception $e) {
            $this->logger->error('JWT token validation failed', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Invalid token');
        }
    }

    public function refreshToken(string $refreshToken): array
    {
        try {
            $payload = $this->validateToken($refreshToken);
            
            if (!isset($payload['type']) || $payload['type'] !== 'refresh') {
                throw new \RuntimeException('Invalid refresh token');
            }

            // Remove token-specific fields from payload
            $newPayload = $payload;
            unset($newPayload['iat'], $newPayload['exp'], $newPayload['type']);

            return $this->generateToken($newPayload);
        } catch (\Exception $e) {
            $this->logger->error('JWT token refresh failed', [
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException('Failed to refresh token: ' . $e->getMessage());
        }
    }

    public function extractTokenFromHeader(): ?string
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;

        if ($authHeader && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function generateSecretKey(): string
    {
        $this->logger->warning('JWT_SECRET not found in environment, generating temporary key');
        return bin2hex(random_bytes(32));
    }

    public function getSecretKey(): string
    {
        return $this->secretKey;
    }

    public function getTokenExpiration(): int
    {
        return $this->tokenExpiration;
    }

    public function getRefreshTokenExpiration(): int
    {
        return $this->refreshTokenExpiration;
    }
}
