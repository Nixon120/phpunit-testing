<?php

namespace Entities;

use Entities\Traits\DataExchangeTrait;
use League\Event\AbstractEvent;
use League\Event\EventInterface;

class Event extends AbstractEvent implements \JsonSerializable, EventInterface
{
    use DataExchangeTrait;

    /**
     * @var string
     */
    private $name;

    /**
     * @var int
     */
    private $entityId;

    /**
     * @var int
     */
    private $attemptCount = 0;

    /**
     * @var ?string
     */
    private $error;

    public function toArray()
    {
        $data = call_user_func('get_object_vars', $this);

        foreach ($data as $key => $value) {
            if ($value instanceof \DateTime) {
                $data[$key] = $value->format('Y-m-d H:i:s');
            }
        }

        unset($data['emitter']);

        return $data;
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return int
     */
    public function getEntityId(): int
    {
        return $this->entityId;
    }

    /**
     * @param int $entityId
     */
    public function setEntityId(int $entityId)
    {
        $this->entityId = $entityId;
    }

    /**
     * @return int
     */
    public function getAttemptCount(): int
    {
        return $this->attemptCount;
    }

    /**
     * @param int $attemptCount
     */
    public function setAttemptCount(int $attemptCount)
    {
        $this->attemptCount = $attemptCount;
    }

    public function incrementAttemptCount()
    {
        $this->attemptCount++;
    }

    /**
     * @return bool
     */
    public function isFirstAttempt(): bool
    {
        return $this->attemptCount === 0;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function setError(?string $error)
    {
        $this->error = $error;
    }
}
