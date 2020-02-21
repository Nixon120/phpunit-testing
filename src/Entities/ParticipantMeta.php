<?php
namespace Entities;

use Entities\Traits\TimestampTrait;

class ParticipantMeta extends Base
{
    use TimestampTrait;

    public $participant_id;

    public $key_id;

    private $key;

    public $value;

    public function __construct(array $data = null)
    {
        parent::__construct();

        if (!is_null($data)) {
            $this->exchange($data);
        }
    }

    /**
     * @return mixed
     */
    public function getParticipantId()
    {
        return $this->participant_id;
    }

    /**
     * @param mixed $id
     */
    public function setParticipantId($id)
    {
        $this->participant_id = $id;
    }

    /**
     * @return mixed
     */
    public function getKeyId()
    {
        return $this->key_id;
    }

    /**
     * @param mixed $keyId
     */
    public function setKeyId($keyId): void
    {
        $this->key_id = $keyId;
    }

    /**
     * @return mixed
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param mixed $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }
}
