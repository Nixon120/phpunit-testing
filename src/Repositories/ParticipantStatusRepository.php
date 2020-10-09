<?php

namespace Repositories;

use AllDigitalRewards\RewardStack\Entities\ParticipantStatus;
use AllDigitalRewards\RewardStack\Services\Participant\StatusEnum\StatusEnum;
use AllDigitalRewards\Services\Catalog\Client;
use Entities\Participant;
use PDO;

class ParticipantStatusRepository extends BaseRepository
{
    protected $table = 'Participant';

    /**
     * @var Client
     */
    private $catalog;

    public function __construct(PDO $database, Client $catalog)
    {
        parent::__construct($database);
        $this->catalog = $catalog;
    }

    public function getRepositoryEntity()
    {
        return ParticipantStatus::class;
    }

    public function getCurrentParticipantStatus($participantId)
    {
        //get current status if exists
        $sql = "SELECT * FROM `participant_status` WHERE participant_id = ? ORDER BY id DESC LIMIT 1";
        $args = [$participantId];
        return $this->query($sql, $args, ParticipantStatus::class);
    }

    /**
     * @param Participant $participant
     * @return int|mixed|string
     */
    public function hydrateParticipantStatusResponse(Participant $participant)
    {
        $status = StatusEnum::INACTIVE;
        //we still do this for backwards compatibility
        if ($participant->isActive() === true) {
            $status = StatusEnum::ACTIVE;
        }
        if ($participant->isFrozen() === true) {
            $status = StatusEnum::HOLD;
        }

        //lets make sure it exists in new table
        $participantStatus = $this->getCurrentParticipantStatus($participant->getId());
        if ($participantStatus) {
            $status = $participantStatus->status;
        }

        return StatusEnum::hydrateStatus($status, true);
    }

    /**
     * @param $participantId
     * @param $status
     * @return bool
     */
    public function saveParticipantStatus($participantId, $status)
    {
        $status = StatusEnum::hydrateStatus($status);
        $currentStatus = $this->getCurrentParticipantStatus($participantId);
        //get current participant status if exists
        //if same as insert then just return
        if ($currentStatus && $currentStatus->status == $status) {
            return true;
        }
        $this->table = 'participant_status';
        $participantStatus = new ParticipantStatus();
        $participantStatus->setParticipantId($participantId);
        $participantStatus->setStatus((int)$status);
        $this->place($participantStatus);
        return true;
    }

    /**
     * @param $status
     * @return bool
     */
    public function hasValidStatus($status)
    {
        return StatusEnum::isValidValue($status) || StatusEnum::isValidName($status);
    }

    /**
     * For backwards compatibility
     *
     * @param $data
     * @return array
     */
    public function getStatus($data): array
    {
        $status = StatusEnum::ACTIVE;
        if (array_key_exists('frozen', $data) === false) {
            $data['frozen'] = 0;
        } else {
            $status = $data['frozen'] == 1 ? StatusEnum::HOLD : StatusEnum::ACTIVE;
        }

        //if `status` exists this will take precedence
        if (array_key_exists('status', $data) === true) {
            $status = $data['status'];
            unset($data['status']);
        }

        return array($status, $data);
    }
}
