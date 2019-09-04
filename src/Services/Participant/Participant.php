<?php

namespace Services\Participant;

use Controllers\Interfaces as Interfaces;
use Entities\ParticipantMeta;
use Repositories\ParticipantRepository;

class Participant
{
    /**
     * @var ParticipantRepository
     */
    public $repository;

    /**
     * @var string
     */
    private $errorMessage;

    public function __construct(ParticipantRepository $repository)
    {
        $this->repository = $repository;
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
                $input->getOffset(),
                30
            );
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
    )
    {
        $filter = new FilterNormalizer([
            'program' => $program_unique_id,
            'points_greater_than' => $points,
            'status' => 1,
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

        if ($participant !== null && $participant->getSso() === $token && $this->repository->purgeParticipantSso($participant->getId())) {
            return $participant;
        }

        return null;
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

    public function generateSso($organization, $uniqueId): ?array
    {
        $participant = $this->repository->getParticipantByOrganization($organization, $uniqueId);
        if ($this->isSsoRequestValid($participant) === false) {
            return [
                'error' => true,
                'message' => $this->errorMessage
            ];
        }

        $participant->setSso($participant->generateSsoToken());
        $aParticipantUpdateRequest = ['sso' => $participant->getSso()];
        if ($this->update($participant->getUniqueId(), $aParticipantUpdateRequest)) {
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
     * @param $data
     * @return false|\Entities\Participant
     */
    public function insert($data)
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

        if (isset($data['active']) && (int) $data['active'] === 0) {
            $data['deactivated_at'] = (new \DateTime)->format('Y-m-d H:i:s');
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
            // unique_id has already been assigned to another Organization.
            $this->repository->setErrors(
                [
                    'unique_id' => [
                        'Unique::ILLEGAL_CHARACTERS' => _("The participant id characters must be alphanumeric, hyphen and/or dashes.")
                    ]
                ]
            );

            return false;
        }

        if ($this->repository->insert($participant->toArray())) {
            $participant = $this->repository->getParticipant($participant->getUniqueId());
            if ($address !== null) {
                $participant->setAddress($address);
                $this->repository->insertAddress($participant->getAddress());
            }

            if ($meta !== null) {
                $this->repository->saveMeta($participant->getId(), $meta);
            }
            return $this->repository->getParticipant($participant->getUniqueId());
        }

        return false;
    }

    private function isParticipantUniqueIdValid($uniqueId)
    {
        if(preg_match('/[^a-z_\-0-9]/i', $uniqueId)) {
            return false;
        }

        return true;
    }

    /**
     * @param $id
     * @param $data
     * @return false|\Entities\Participant
     */
    public function update($id, $data)
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

        if (isset($data['active'])) {
            $statusFlag = (int) $data['active'];
            if ($statusFlag === 1) {
                $data['deactivated_at'] = null;
            } elseif ($statusFlag === 0 && $participant->getDeactivatedAt() === null) {
                $data['deactivated_at'] = (new \DateTime)->format('Y-m-d H:i:s');
            }
        }

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

        if ($this->repository->update($participant->getId(), $data)
        ) {
            if ($meta !== null) {
                $this->repository->saveMeta($participant->getId(), $meta);
            }
            return $this->repository->getParticipant($participant->getUniqueId());
        }

        return false;
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
        foreach($collection as $value) {
            $returnCollection[key($value)] = $value[key($value)];
        }

        return $returnCollection;
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
        foreach($meta as $k=>$v) {
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

    public function getErrors()
    {
        return $this->repository->getErrors();
    }
}
