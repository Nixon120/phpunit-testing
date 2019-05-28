<?php

namespace Factories;

use Firebase\JWT\JWT;

class AuthenticationTokenFactory
{
    /**
     * @var string
     */
    private static $token;

    public static function getToken()
    {
        if (self::$token === null) {
            $now = new \DateTime();
            $future = new \DateTime("now +2 hours");
            $jti = base64_encode(random_bytes(16));
            $payload = [
                "iat" => $now->getTimeStamp(),
                "exp" => $future->getTimeStamp(),
                "jti" => $jti,
                "sub" => 'rewardstack@alldigitalrewards.com',
                "user" => [
                    'id' => 0,
                    'firstname' => 'Event Task Runner',
                    'lastname' => 'System'
                ],
                "scope" => ['*.all'] // This means nothing. This isn't needed for anything but RewardStack..
            ];

            $secret = getenv("JWT_SECRET");
            $token = JWT::encode($payload, $secret, "HS256");
            $data["token"] = $token;
            $data["expires"] = $future->getTimeStamp();
            self::$token = $data['token'];
        }

        return self::$token;
    }
}
