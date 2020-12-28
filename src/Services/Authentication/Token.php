<?php
namespace Services\Authentication;

use AllDigitalRewards\UserAccessLevelEnum\UserAccessLevelEnum;
use Entities\User;
use Firebase\JWT\JWT;

class Token
{
    /**
     * @var array
     */
    private $availableScopes = [];

    /**
     * @var array
     */
    public $requestedScopes = [];

    public $decoded;

    /**
     * @var ?\DateTime
     */
    private $expiry;

    /**
     * @var ?string
     */
    private $token;

    public function __construct(array $availableScopes = [])
    {
        $this->availableScopes = $availableScopes;
    }

    public function setRequestedScopes(array $requestedScopes)
    {
        $scopes = array_filter($requestedScopes, function ($needle) {
            return in_array($needle, $this->availableScopes);
        });
        //@TODO if count (requestedScopes vs scopes) doesn't match throw exception?
        $this->requestedScopes = $scopes;
    }

    public function getAvailableScopes()
    {
        return $this->availableScopes;
    }

    public function getRequestedScopes()
    {
        return $this->requestedScopes;
    }

    public function setExpiry(?string $expiry)
    {
        $this->expiry = $expiry;
    }

    public function getExpiry():\DateTime
    {
        if (!$this->expiry instanceof \DateTime) {
            $this->expiry = (new \DateTime())->setTimestamp($this->expiry);
        }
        return $this->expiry;
    }

    public function isTokenValid(): bool
    {
        $now = new \DateTime;
        if (is_null($this->getToken()) || is_null($this->getExpiry()) || $this->getExpiry() < $now) {
            return false;
        }
        return true;
    }

    public function setToken(string $token)
    {
        $this->token = $token;
    }

    public function getToken():?string
    {
        return $this->token;
    }

    public function hydrate($decoded)
    {
        $this->decoded = $decoded;
    }

    public function generateUserToken(User $user)
    {
        $now = new \DateTime();
        $future = new \DateTime("now +2 hours");
        $jti = base64_encode(random_bytes(16));
        $payload = [
            "iat" => $now->getTimeStamp(),
            "exp" => $future->getTimeStamp(),
            "jti" => $jti,
            "sub" => $user->getEmailAddress(),
            "user" => [
                'id' => $user->getId(),
                'role' => $user->getRole(),
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
                'access_level' => (new UserAccessLevelEnum())->hydrateLevel($user->getAccessLevel(), true)
            ],
            "scope" => $this->getRequestedScopes()
        ];
        $secret = getenv("JWT_SECRET");
        $token = JWT::encode($payload, $secret, "HS256");
        $data["token"] = $token;
        $data["expires"] = $future->getTimeStamp();
        return $data;
    }
}
