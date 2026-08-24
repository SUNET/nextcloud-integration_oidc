<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Micke Nordin <kano@sunet.se>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\IOIDC\BackgroundJob;

use OCA\IOIDC\Db\IOIDCUserMapper;
use OCA\IOIDC\Service\OidcClient;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use Psr\Log\LoggerInterface;
use Throwable;

class RefreshTokens extends TimedJob
{
    private const INTERVAL = 3600;

    public function __construct(
        ITimeFactory $time,
        private IOIDCUserMapper $ioidcUserMapper,
        private LoggerInterface $logger,
        private OidcClient $oidcClient,
    ) {
        parent::__construct($time);
        $this->setInterval(self::INTERVAL);
        $this->setAllowParallelRuns(false);
    }

    protected function run($arguments): void
    {
        foreach ($this->ioidcUserMapper->get_all_accesstoken() as $token) {
            if ((int)$token['timestamp'] + (int)$token['expires_in'] >= time() + self::INTERVAL) {
                continue;
            }

            try {
                $this->refreshToken($token);
            } catch (Throwable $e) {
                $this->logger->warning('OIDC token refresh failed', [
                    'connectionId' => (int)$token['id'],
                    'providerId' => (int)$token['provider_id'],
                    'reason' => $e::class,
                ]);
            }
        }
    }

    /** @param array<string, mixed> $token */
    private function refreshToken(array $token): void
    {
        $response = $this->oidcClient->refreshToken(
            $token,
            (string)$token['refresh_token'],
            (string)$token['scope'],
        );

        $this->ioidcUserMapper->refresh_token([
            'access_token' => $response['access_token'],
            'expires_in' => $response['expires_in'],
            'id' => (int)$token['id'],
            'original_revision' => (int)$token['revision'],
            'provider_version' => (int)$token['provider_version'],
            'refresh_token' => is_string($response['refresh_token'])
                ? $response['refresh_token']
                : (string)$token['refresh_token'],
            'scope' => $response['scope'] !== '' ? $response['scope'] : (string)$token['scope'],
            'token_type' => $response['token_type'],
            'uid' => (string)$token['uid'],
        ]);
    }
}
