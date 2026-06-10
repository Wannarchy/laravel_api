<?php

namespace App\Auth;

use Illuminate\Auth\Passwords\PasswordBrokerManager;

class CustomPasswordBrokerManager extends PasswordBrokerManager
{
    protected function createTokenRepository(array $config)
    {
        if (($config['driver'] ?? 'database') === 'utilisateurs') {
            return new UtilisateurTokenRepository(
                $this->app['db']->connection($config['connection'] ?? null),
                ($config['expire'] ?? 60) * 60,
                $config['throttle'] ?? 60,
            );
        }

        return parent::createTokenRepository($config);
    }
}
