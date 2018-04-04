<?php
namespace Entities;

use Entities\Traits\TimestampTrait;

class Webhook extends Base
{
    use TimestampTrait;

    /**
     * @var int
     */
    public $id;

    /**
     * @var int
     */
    public $organization_id;

    /**
     * @var string
     */
    public $title;

    /**
     * @var string
     */
    public $url;

    /**
     * @var string
     */
    public $username;

    /**
     * @var string
     */
    public $password;

    /**
     * @var string
     */
    public $event;

    /**
     * @var int
     */
    public $active = 0;

    /**
     * @var int
     */
    public $immutable = 0;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getOrganizationId(): int
    {
        return $this->organization_id;
    }

    /**
     * @param int $organization_id
     */
    public function setOrganizationId(int $organization_id)
    {
        $this->organization_id = $organization_id;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title)
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        if (is_null($this->url)) {
            return '';
        }

        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl(string $url)
    {
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        if (is_null($this->username)) {
            return '';
        }

        return $this->username;
    }

    /**
     * @param string $username
     */
    public function setUsername(string $username)
    {
        $this->username = $username;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        if (is_null($this->password)) {
            return '';
        }

        return $this->password;
    }

    /**
     * @param string $password
     */
    public function setPassword(string $password)
    {
        $this->password = $password;
    }

    /**
     * @return string
     */
    public function getEvent(): string
    {
        if (is_null($this->event)) {
            return '';
        }

        return $this->event;
    }

    /**
     * @param string $event
     */
    public function setEvent(string $event)
    {
        $this->event = $event;
    }

    /**
     * @return bool
     */
    public function isImmutable(): bool
    {
        if ($this->immutable === 1) {
            return true;
        }

        return false;
    }

    /**
     * @param int $immutable
     */
    public function setImmutable(int $immutable)
    {
        $this->immutable = $immutable;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        if (empty($this->password)) {
            // If it's 0 or null, the webhook is inactive.
            return false;
        }

        return true;
    }

    public function setActive()
    {
        $this->active = 1;
    }

    public function setInactive()
    {
        $this->active = 0;
    }
}
