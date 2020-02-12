<?php
namespace Repositories;

use AllDigitalRewards\Services\Catalog\Client;
use Entities\AutoRedemption;
use Entities\Contact;
use Entities\Domain;
use Entities\OfflineRedemption;
use Entities\Organization;
use Entities\Program;
use Entities\Address;
use Entities\Participant;
use Entities\ParticipantMeta;
use Entities\Sweepstake;
use \PDO as PDO;

class ParticipantRepository extends BaseRepository
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
        return Participant::class;
    }

    public function getCollectionQuery(): string
    {
        $where = " WHERE 1 = 1 ";

        if (!empty($this->getProgramIdContainer())) {
            $programIdString = implode(',', $this->getProgramIdContainer());
            $where = <<<SQL
WHERE Program.id IN ({$programIdString})
SQL;
        }

        return <<<SQL
SELECT Participant.id, Participant.program_id, Program.unique_id as program_reference, 
    Organization.unique_id as organization_reference, email_address, 
    Participant.unique_id, credit, firstname, lastname, Participant.active, 
    Participant.updated_at, Participant.created_at FROM Participant
JOIN Organization ON Organization.id = Participant.organization_id
JOIN Program ON Program.id = Participant.program_id
{$where}
SQL;
    }

    public function getParticipantByOrganization($organizationId, $uniqueId)
    {
        //@TODO: This needs to be updated to check for org id, for overlap on unique id between orgs.
        return $this->getParticipant($uniqueId);
    }

    public function purgeParticipantSso($id)
    {
        $sql = "UPDATE Participant SET sso = NULL WHERE id = ?";
        $args = [$id];

        $sth = $this->database->prepare($sql);
        return $sth->execute($args);
    }

    //@TODO: update all references of getParticipant to include org (or program?) id.
    public function getParticipant($uniqueId, $hydrate = true)
    {
        $scopeWhere = "";

        if (!empty($this->getProgramIdContainer())) {
            $programIdString = implode(',', $this->getProgramIdContainer());
            $scopeWhere = <<<SQL
AND program_id IN ({$programIdString})
SQL;
        }

        $sql = <<<SQL
SELECT * FROM `Participant` 
WHERE unique_id = ? {$scopeWhere}
SQL;
        if (!$participant = $this->query($sql, [$uniqueId], Participant::class)) {
            return null;
        }

        if ($hydrate === false) {
            return $participant;
        }

        return $this->hydrateParticipant($participant);
    }

    //@TODO: update all references of getParticipant to include org (or program?) id.
    public function getParticipantById($primaryId)
    {
        $sql = "SELECT * FROM `Participant` WHERE id = ?";

        if (!$participant = $this->query($sql, [$primaryId], Participant::class)) {
            return null;
        }

        return $this->hydrateParticipant($participant);
    }

    private function hydrateParticipant(Participant $participant)
    {
        $program = $this->getParticipantProgram($participant->getProgramId());
        $participant->setProgram($program);
        $participant->setOrganization($program->getOrganization());
        $meta = $this->getParticipantMeta($participant->getId());
        $participant->setMeta($meta);
        if ($participant->getAddressReference() !== null) {
            $address = $this->getAddressByReference($participant->getId(), $participant->getAddressReference());
            if ($address instanceof Address) {
                $participant->setAddress($address->toArray());
            }
        }
        return $participant;
    }

    public function deleteParticipantMeta($participantId)
    {
        $sql = "DELETE FROM `ParticipantMeta` WHERE participant_id = ?";
        $sth = $this->database->prepare($sql);
        return $sth->execute([$participantId]);
    }

    private function deleteMetaByParticipantAndKey($participantId, $key)
    {
        $sql = "DELETE FROM `ParticipantMeta` WHERE participant_id = ? AND `key` = ?";
        $sth = $this->database->prepare($sql);
        return $sth->execute([$participantId, $key]);
    }

    public function setParticipantMeta($metaCollection): bool
    {
        $this->table = 'ParticipantMeta';
        //@TODO try / catch
        foreach ($metaCollection as $meta) {
            /** @var ParticipantMeta $meta */
            if ($meta->getValue() === null) {
                //purge
                if (!$this->deleteMetaByParticipantAndKey($meta->getParticipantId(), $meta->getKey())) {
                    return false;
                }
            } else {
                if (!$this->place($meta)) {
                    return false;
                }
            }
        }
        $this->table = 'Participant';
        return true;
    }

    public function getParticipantMeta($participantId)
    {
        $sql = "SELECT * FROM `ParticipantMeta` WHERE participant_id = ?";
        $args = [$participantId];
        $sth = $this->database->prepare($sql);
        $sth->execute($args);
        if ($meta = $sth->fetchAll(PDO::FETCH_CLASS, ParticipantMeta::class)) {
            return $this->prepareParticipantMeta($meta);
        }

        return [];
    }

    private function prepareParticipantMeta($meta):array
    {
        $associative = [];
        foreach ($meta as $key => $value) {
            $associative[] = [$value->getKey() => $value->getValue()];
        }

        return $associative;
    }

    public function getAddressByReference($participantId, $ref)
    {
        $sql = "SELECT * FROM `Address` WHERE participant_id = ? AND reference_id = ?";
        $args = [$participantId, $ref];
        return $this->query($sql, $args, Address::class);
    }

    public function insertAddress(Address $address)
    {
        $this->table = 'Address';
        $result = parent::insert($address->toArray(), true);
        $this->table = 'Participant';

        return $result;
    }

    public function getParticipantOrganization(?string $id, $unique = false):?Organization
    {
        $identifier = $unique ? 'unique_id' : 'id';
        $sql = "SELECT * FROM `Organization` WHERE {$identifier} = ?";
        $args = [$id];
        if (!$organization = $this->query($sql, $args, Organization::class)) {
            return null;
        };

        return $organization;
    }

    //@TODO should we figure out a way to pull this method from the program repository? Might be excessive, but more dry?
    public function getParticipantProgram(?string $id, $unique = false):?Program
    {
        $identifier = $unique ? 'unique_id' : 'id';
        $sql = "SELECT * FROM `Program` WHERE {$identifier} = ?";
        $args = [$id];

        if (!$program = $this->query($sql, $args, Program::class)) {
            return null;
        }


        return $this->hydrateParticipantProgram($program);
    }

    public function hydrateParticipantProgram(Program $program)
    {
        $program->setOrganization($this->getProgramOrganization($program->getOrganizationId()));
        $program->setDomain($this->getProgramDomain($program->getDomainId()));
        $program->setAutoRedemption($this->getAutoRedemption($program));
        $programContact = $this->getContact($program);
        if ($programContact instanceof Contact) {
            $program->setContact($programContact);
        }
        $program->setSweepstake($this->getProgramSweepstake($program));
        return $program;
    }

    private function hydrateAutoRedemption(AutoRedemption $autoRedemption): AutoRedemption
    {
        if ($autoRedemption->getProductSku() === null) {
            return $autoRedemption;
        }

        $product = $this->catalog->getProduct($autoRedemption->getProductSku());
        if ($product === null) {
            return $autoRedemption;
        }
        $autoRedemption->setProduct($product);
        return $autoRedemption;
    }

    public function getAutoRedemption(Program $program):?AutoRedemption
    {
        $sql = "SELECT * FROM `AutoRedemption` WHERE program_id = ?";
        $args = [$program->getId()];
        if (!$autoRedemption = $this->query($sql, $args, AutoRedemption::class)) {
            return null;
        }

        /** @var AutoRedemption $autoRedemption */
        $autoRedemption->setProgram($program);
        return $this->hydrateAutoRedemption($autoRedemption);
    }

    public function getContact(Program $program)
    {
        $sql = "SELECT * FROM `Contact` WHERE reference_id = ?";
        $args = [$program->getContactReference()];
        if (!$contact = $this->query($sql, $args, Contact::class)) {
            return null;
        }

        return $contact;
    }

    public function getProgramOrganization(?string $id, $unique = false):?Organization
    {
        $sql = "SELECT * FROM `Organization` WHERE ";

        if ($unique) {
            $sql .= 'unique_id = ?';
        } else {
            $sql .= 'id = ?';
        }

        $args = [$id];
        return $this->query($sql, $args, Organization::class);
    }

    public function getOfflineRedemptions(Program $program)
    {
        $sql = "SELECT * FROM `OfflineRedemption` WHERE program_id = ?";
        $args = [$program->getId()];
        $sth = $this->database->prepare($sql);
        $sth->execute($args);

        $offlineRedemptions = $sth->fetchAll(PDO::FETCH_CLASS, OfflineRedemption::class);
        if (empty($offlineRedemptions) === true) {
            return [];
        }

        //fetch approved skus
        $skus = json_decode($offlineRedemptions[0]->getSkus());

        return $skus;
    }

    public function getProgramSweepstake(Program $program)
    {
        $sql = "SELECT * FROM `Sweepstake` WHERE program_id = ?";
        $args = [$program->getUniqueId()];
        if (!$sweepstake = $this->query($sql, $args, Sweepstake::class)) {
            return null;
        }

        return $sweepstake;
    }

    //@TODO should we figure out a way to pull this method from the domain repository? Might be excessive, but more dry?
    public function getProgramDomain(?string $id):?Domain
    {
        $sql = "SELECT * FROM `Domain` WHERE id = ?";
        $args = [$id];
        return $this->query($sql, $args, Domain::class);
    }

    public function saveMeta($participantId, array $meta)
    {
        $metaCollection = [];
        $date = new \DateTime;
        foreach ($meta as $item) {
            foreach ($item as $key => $value) {
                $newMeta = new ParticipantMeta;
                $newMeta->setKey($key);
                $newMeta->setValue($value);
                $newMeta->setParticipantId($participantId);
                $newMeta->setUpdatedAt($date->format('Y-m-d H:i:s'));
                // don't include meta that is null or empty string
                if (!empty($value)) {
                    $metaCollection[] = $newMeta;
                }
            }
        }

        return $this->setParticipantMeta($metaCollection);
    }
}
