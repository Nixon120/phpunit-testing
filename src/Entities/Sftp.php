<?php

namespace Entities;

use Entities\Traits\TimestampTrait;

class Sftp extends Base
{
    use TimestampTrait;

    /**
     * @var string
     */
    public $host;
    /**
     * the filename
     *
     * @var string
     */
    public $port;
    /**
     * the filename
     *
     * @var string
     */
    public $file_path;
    /**
     * the filename
     *
     * @var string
     */
    public $username;
    /**
     * the filename
     *
     * @var string
     */
    public $password;
    /**
     * the filename
     *
     * @var string
     */
    public $key;
    /**
     * user.id reference
     *
     * @var integer
     */
    public $user_id;

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @param string $host
     */
    public function setHost(string $host)
    {
        $this->host = $host;
    }

    /**
     * @return string
     */
    public function getPort(): string
    {
        return $this->port;
    }

    /**
     * @param string $port
     */
    public function setPort(string $port)
    {
        $this->port = $port;
    }

    /**
     * @return string
     */
    public function getFilePath(): string
    {
        return $this->file_path;
    }

    /**
     * @param string $file_path
     */
    public function setFilePath(string $file_path)
    {
        $this->file_path = $file_path;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
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
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @param string $key
     */
    public function setKey(string $key)
    {
        $this->key = $key;
    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->user_id;
    }

    /**
     * @param int $user_id
     */
    public function setUserId(int $user_id): void
    {
        $this->user_id = $user_id;
    }
}
