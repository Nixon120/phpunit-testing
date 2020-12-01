<?php

namespace Services\Participant;

use AllDigitalRewards\RewardStack\Traits\MetaValidationTrait;
use AllDigitalRewards\StatusEnum\StatusEnum;
use Controllers\Interfaces as Interfaces;
use Controllers\Participant\InputNormalizer;
use Entities\User;
use Exception;
use Repositories\ParticipantRepository;
use Traits\LoggerAwareTrait;

class Participant
{
    use MetaValidationTrait;
    use LoggerAwareTrait;

    /**
     * @var ParticipantRepository
     */
    public $repository;
    /**
     * @var StatusEnum
     */
    private $statusEnumService;
    /**
     * @var string
     */
    private $errorMessage;

    public function __construct(ParticipantRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @return StatusEnum
     */
    public function getStatusEnumService(): StatusEnum
    {
        if ($this->statusEnumService === null) {
            $this->statusEnumService = new StatusEnum();
        }
        return $this->statusEnumService;
    }

    /**
     * @param StatusEnum $statusEnumService
     */
    public function setStatusEnumService(StatusEnum $statusEnumService): void
    {
        $this->statusEnumService = $statusEnumService;
    }

    public function getById($id): ?\Entities\Participant
    {
        $participant = $this->repository->getParticipantById($id);

        if ($participant) {
            return $participant;
        }

        return null;
    }

    public function getSingle($id): ?\Entities\Participant
    {
        $participant = $this->repository->getParticipant($id);

        if ($participant) {
            return $participant;
        }

        return null;
    }

    public function get(Interfaces\InputNormalizer $input)
    {
        $filter = new FilterNormalizer($input->getInput());
        $participants = $this
            ->repository
            ->getCollection(
                $filter,
                $input->getPage(),
                $input->getLimit()
            );

        foreach ($participants as $key => $participant) {
            $statusName = $this->getStatusEnumService()->hydrateStatus($participant->getStatus(), true);
            $participant->setStatus($statusName);
            $participants[$key] = $participant;
        }

        return $participants;
    }

    /**
     * @param $program_unique_id
     * @param $points
     * @return \Entities\Participant[]
     */
    public function getProgramParticipantsWithPointsGreaterThan(
        $program_unique_id,
        $points
    ) {
        $filter = new FilterNormalizer([
            'program' => $program_unique_id,
            'points_greater_than' => $points,
            'status' => $this->getStatusEnumService()::ACTIVE,
            'active' => 1,
        ]);

        $participants = $this
            ->repository
            ->getCollection(
                $filter,
                0,
                100000
            );

        return $participants;
    }

    public function authenticateSso($organization, $uniqueId, $token)
    {
        $participant = $this->repository->getParticipantByOrganization($organization, $uniqueId);

        if ($participant !== null
            && $this->getStatusEnumService()
                ->hydrateStatus($participant->getStatus())
                === $this->getStatusEnumService()::ACTIVE
            && $participant->isActive() === true
            && $participant->getSso() === $token
            && $this->repository->purgeParticipantSso($participant->getId())
        ) {
            return $participant;
        }

        return null;
    }

    public function generateSso(User $authUser, $uniqueId): ?array
    {
        $participant = $this->repository->getParticipantByOrganization($authUser->getOrganizationId(), $uniqueId);
        if ($this->isSsoRequestValid($participant) === false) {
            return [
                'error' => true,
                'message' => $this->errorMessage
            ];
        }

        $participant->setSso($participant->generateSsoToken());
        $aParticipantUpdateRequest = ['sso' => $participant->getSso()];
        if ($this->update($participant->getUniqueId(), $aParticipantUpdateRequest, $authUser->getEmailAddress())) {
            $program = $participant->getProgram();
            $domain = $program->getDomain();
            $exchange = implode('.', [$program->getUrl(), $domain->getUrl()]);
            $exchange .= '/?authenticate='
                . $participant->getSso()
                . '&' . 'participant='
                . $participant->getUniqueId();

            return [
                'token' => $participant->getSso(),
                'participant' => $participant->getUniqueId(),
                'domain' => $domain->getUrl(),
                'exchange' => 'https://' . $exchange
            ];
        }

        return [
            'error' => true,
            'message' => 'There was a problem with your request'
        ];
    }

    /**
     * @param $data
     * @param string $agentEmail
     * @return false|\Entities\Participant
     */
    public function insert($data, string $agentEmail)
    {
        //@TODO API Exceptions
        if (!empty($data['program'])) {
            $program = $this->repository->getParticipantProgram($data['program'], true);

            if ($program === null) {
                $this->repository->setErrors(
                    [
                        'program' => [
                            'NotFound::NOT_FOUND' => _("The program requested does not exist.")
                        ]
                    ]
                );

                return false;
            }

            $data['program_id'] = $program->getId();
            $data['organization_id'] = $program->getOrganizationId();
        }

        $address = $data['address'] ?? null;
        $meta = $data['meta'] ?? null;
        unset($data['program'], $data['organization'], $data['address'], $data['shipping'], $data['meta'], $data['password_confirm']);

        if (!empty($data['birthdate'])) {
            $data['birthdate'] = $this->getDate($data['birthdate'])
                ->format('Y-m-d');
        } else {
            $data['birthdate'] = null;
        }

        if (isset($data['active']) && $data['active'] != 1) {
            $data['deactivated_at'] = (new \DateTime)->format('Y-m-d H:i:s');
        }

        list($status, $data) = $this->repository->hydrateParticipantStatusRequest($data);
        if ($this->repository->hasValidStatus($status) === false) {
            $this->repository->setErrors(
                [
                    'status' => [
                        'Status::ILLEGAL_CHARACTERS' => _("The status is not valid, please refer to docs for acceptable types.")
                    ]
                ]
            );
            return false;
        }

        $participant = new \Entities\Participant;
        $participant->exchange($data);
        if ($address !== null) {
            $participant->setAddress($address);
        }

        if (!$this->participantIdIsUnique($participant->getUniqueId())) {
            // unique_id has already been assigned to another Organization.
            $this->repository->setErrors(
                [
                    'unique_id' => [
                        'Unique::NOT_UNIQUE' => _("The participant id has already been assigned to another participant.")
                    ]
                ]
            );
            return false;
        }

        if (!$this->isParticipantUniqueIdValid($participant->getUniqueId())) {
            $this->repository->setErrors(
                [
                    'unique_id' => [
                        'Unique::ILLEGAL_CHARACTERS' => _("The participant id characters must be alphanumeric, hyphen and/or dashes.")
                    ]
                ]
            );
            return false;
        }

        if ($this->hasValidMeta($meta) === false) {
            return false;
        }

        $participantArray = $participant->toArray();
        unset($participantArray['status']); //prevent insert error
        if ($this->repository->insert($participantArray)) {
            $participant = $this->repository->getParticipant($participant->getUniqueId());
            $this->repository->saveParticipantStatus($participant, $status);
            $this->repository->logParticipantChange($participant, $agentEmail, true);
            if ($address !== null) {
                $participant->setAddress($address);
                $this->repository->insertAddress($participant->getAddress());
            }

            if (empty($meta) === false) {
                $this->repository->saveMeta($participant->getId(), $meta);
            }

            $this->setParticipantPiiToEmptyIfDataDelStatus($participant, $agentEmail, $status);
            return $this->repository->getParticipant($participant->getUniqueId());
        }

        return false;
    }

    /**
     * @param $id
     * @param $data
     * @param string $agentEmailAddress
     * @return false|\Entities\Participant
     */
    public function update($id, $data, string $agentEmailAddress)
    {
        $participant = $this->getSingle($id);

        //@TODO this sucks.. fix it someway
        //@TODO API Exceptions
        if (!empty($data['program'])) {
            $program = $this->repository->getParticipantProgram($data['program'], true);
            $data['program_id'] = $program->getId();
            $data['organization_id'] = $program->getOrganizationId();
        }
        if (!empty($data['password'])) {
            $password = $data['password'];
        }

        if (!empty($data['birthdate'])) {
            $data['birthdate'] = $this->getDate($data['birthdate'])
                ->format('Y-m-d');
        } else {
            $data['birthdate'] = null;
        }

        if (array_key_exists('active', $data) === true) {
            $statusFlag = (int) $data['active'];
            if ($statusFlag === 1) {
                $data['deactivated_at'] = null;
            } else {
                $data['deactivated_at'] = (new \DateTime)->format('Y-m-d H:i:s');
            }
        }

        list($status, $data) = $this->repository->hydrateParticipantStatusRequest($data);
        if ($this->repository->hasValidStatus($status) === false) {
            $this->repository->setErrors(
                [
                    'status' => [
                        'Status::ILLEGAL_CHARACTERS' => _("The status is not valid, please refer to docs for acceptable types.")
                    ]
                ]
            );
            return false;
        }
        $this->repository->saveParticipantStatus($participant, $status);

        $address = $data['address'] ?? null;
        $meta = $data['meta'] ?? null;
        unset($data['program'], $data['organization'], $data['password'], $data['address'], $data['meta'], $data['password_confirm'], $data['unique_id']);

        $participant->exchange($data);

        if ($address !== null) {
            $participant->setAddress($address);
            $data['address_reference'] = $participant->getAddressReference();
            //@TODO if failure throw exception
            $this->repository->insertAddress($participant->getAddress());
        }

        if (!empty($password)) {
            $data['password'] = $password;
            $data = $this->hydratePassword($data, $participant);
        }

        if ($this->hasValidMeta($meta) === false) {
            return false;
        }

        if ($this->repository->update($participant->getId(), $data)) {
            if ($meta !== null) {
                $this->repository->saveMeta($participant->getId(), $meta);
            }

            $this->repository->logParticipantChange($participant, $agentEmailAddress);

            $this->setParticipantPiiToEmptyIfDataDelStatus($participant, $agentEmailAddress, $status);
            return $this->repository->getParticipant($participant->getUniqueId());
        }

        return false;
    }

    /**
     * @param $meta
     * @return bool
     */
    public function hasValidMeta($meta): bool
    {
        if ($this->hasWellFormedMeta($meta) === false) {
            $this->repository->setErrors([
                'meta' => [
                    'Meta::ILLEGAL_META' => _("Participant Meta is not valid, please provide valid key:value non-empty pairs.")
                ]
            ]);

            return false;
        }

        return true;
    }

    /**
     * @param \Entities\Participant $participant
     * @param $metaData
     * @return bool
     */
    public function updateMeta(\Entities\Participant $participant, $metaData)
    {
        $meta = array_merge($this->simplifyMetaCollection($participant->getMeta()), $this->simplifyMetaCollection($metaData));
        $metaCollection = [];
        foreach ($meta as $k => $v) {
            $metaCollection[] = [$k => $v];
        }

        return $this->repository->saveMeta($participant->getId(), $metaCollection);
    }

    /**
     * @param \Entities\Participant $participant
     * @param $meta
     * @return bool
     */
    public function saveMeta(\Entities\Participant $participant, $meta)
    {
        // We need to clear existing meta.
        $this->repository->deleteParticipantMeta($participant->getId());
        return $this->repository->saveMeta($participant->getId(), $meta);
    }

    /**
     * @param Balance $balanceService
     * @param \Entities\Participant $participant
     */
    public function setParticipantCreditsToZeroIfCancelled(Balance $balanceService, \Entities\Participant $participant)
    {
        if ($this->getStatusEnumService()->hydrateStatus($participant->getStatus())
            === $this->getStatusEnumService()::CANCELLED
        ) {
            $data = [
                'type' => 'debit',
                'amount' => $participant->getCredit(),
                'description' => 'Participant' . $participant->getUniqueId() . ' is cancelled status',
                'completed_at' => date('Y-m-d H:i:s', strtotime('now'))
            ];
            $input = new InputNormalizer($data);
            $balanceService->createAdjustment($participant, $input);
        }
    }

    public function getErrors()
    {
        return $this->repository->getErrors();
    }

    private function isParticipantUniqueIdValid($uniqueId)
    {
        if(preg_match('/[^a-z_\-0-9]/i', $uniqueId)) {
            return false;
        }

        return true;
    }

    /**
     * Make meta collection easier to work with (for temporary assignments, updates, etc)
     *
     * @param $collection
     * @return array
     */
    private function simplifyMetaCollection($collection)
    {
        $returnCollection = [];
        foreach ($collection as $value) {
            $returnCollection[key($value)] = $value[key($value)];
        }

        return $returnCollection;
    }

    private function isSsoRequestValid(?\Entities\Participant $participant): bool
    {
        if ($participant === null) {
            $this->errorMessage = 'Resource does not exist';
            return false;
        }

        if ($participant->isActive() === false) {
            $this->errorMessage = 'Participant ' . $participant->getUniqueId() . ' is not active';
            return false;
        }

        if ($this->getStatusEnumService()->hydrateStatus($participant->getStatus()) === $this->getStatusEnumService()::HOLD) {
            $this->errorMessage = 'Participant ' . $participant->getUniqueId() . ' has a hold status';
            return false;
        }

        $program = $participant->getProgram();
        $programNameString = 'Program ' . $program->getName() . '[' . $program->getUniqueId() . ']';
        if ($program->isPublished() === false) {
            $this->errorMessage = $programNameString . ' is not published';
            return false;
        }

        if ($program->getDomain() === null) {
            $this->errorMessage = $programNameString . ' does not have a marketplace domain configured';
            return false;
        };

        return true;
    }

    private function participantIdIsUnique($unique_id)
    {
        $exists = $this
            ->repository
            ->getParticipant(
                $unique_id,
                false
            );

        if (is_null($exists)) {
            return true;
        }

        return false;
    }

    /**
     * @param $date
     * @return \DateTime
     */
    private function getDate($date): \DateTime
    {
        $datetime = new \DateTime;
        $timestamp = strtotime($date);
        $datetime->setTimestamp($timestamp);
        return $datetime;
    }

    private function hydratePassword($data, \Entities\Participant $participant)
    {
        if (isset($data['password'])) {
            $password = $data['password'];
            unset($data['password']);
            if (!password_verify($password, $participant->getPassword()) && $password !== "") {
                $participant->setPassword($password);
                $data['password'] = $participant->getPassword();
            } else {
                // We're going to ignore this on update
                $this->repository->setSkip(['password']);
            }
        } else {
            $this->repository->setSkip(['password']);
        }

        return $data;
    }

    /**
     * @param \Entities\Participant $participant
     * @param string $agentEmailAddress
     * @return bool
     */
    public function removeParticipantPii(\Entities\Participant $participant, string $agentEmailAddress)
    {
        try {
            $statusName = $this->getStatusEnumService()->hydrateStatus(StatusEnum::DATADEL, true);
            $this->repository->setParticipantTransactionEmailAddressToEmpty($participant->getId());
            $this->repository->setParticipantAddressPiiToEmpty($participant->getId());
            $this->repository->setParticipantPiiToEmpty($participant->getId());
            $participant->setStatus($statusName);
            $this->repository->saveParticipantStatus($participant, $statusName);
            $this->repository->logParticipantChange($participant, $agentEmailAddress);
            $this->repository->setParticipantToInactive($participant->getId());
            return true;
        } catch (Exception $exception) {
            $this->repository->setErrors([$exception->getMessage()]);
            $this->getLogger()->error(
                'Participant PII Delete Failure',
                [
                    'success' => false,
                    'action' => 'update',
                    'uuid' => $participant->getUniqueId(),
                    'error' => $exception->getMessage()
                ]
            );
            return false;
        }
    }

    /**
     * @param \Entities\Participant $participant
     * @param string $agentEmailAddress
     * @param $status
     */
    private function setParticipantPiiToEmptyIfDataDelStatus(
        \Entities\Participant $participant,
        string $agentEmailAddress,
        $status
    ): void {
        $statusId = $this->getStatusEnumService()->hydrateStatus($status);
        if ($statusId === StatusEnum::DATADEL) {
            $this->removeParticipantPii($participant, $agentEmailAddress);
        }
    }
}
