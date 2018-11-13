<?php
namespace Repositories;

use Entities\Adjustment;
use Entities\Participant;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validator;

class BalanceRepository extends BaseRepository
{
    protected $table = 'Adjustment';

    public function getAdjustment(Participant $participant, int $id)
    {
        $sql = "SELECT * FROM Adjustment WHERE participant_id = ? AND id = ?";
        $args = [$participant->getId(), $id];
        /** @var Adjustment $adjustment */
        if (!$adjustment = $this->query($sql, $args, Adjustment::class)) {
            return null;
        }

        $adjustment->setParticipant($participant);
        return $adjustment;
    }

    public function getAdjustmentForWebhook(int $id)
    {
        $sql = "SELECT * FROM Adjustment WHERE id = ?";
        $args = [$id];
        /** @var Adjustment $adjustment */
        if (!$adjustment = $this->query($sql, $args, Adjustment::class)) {
            return null;
        }

        return $adjustment;
    }

    public function getAdjustmentsByParticipant(Participant $participant)
    {
        $sql = "SELECT Adjustment.* "
            . " FROM Adjustment "
            . " WHERE participant_id = ?"
            . " ORDER BY created_at DESC";

        $args = [$participant->getId()];
        /** @var Adjustment $adjustment */
        $sth = $this->database->prepare($sql);
        $sth->execute($args);

        $adjustments = $sth->fetchAll(\PDO::FETCH_CLASS, $this->getRepositoryEntity());

        if (empty($adjustments)) {
            return [];
        }

        foreach ($adjustments as $adjustment) {
            $adjustment->setParticipant($participant);
        }

        return $adjustments;
    }

    public function getRepositoryEntity()
    {
        return Adjustment::class;
    }

    public function getCollectionQuery(): string
    {
        return <<<SQL
SELECT Adjustment.* 
FROM `Adjustment` 
LEFT JOIN Participant ON Participant.id = Adjustment.participant_id
WHERE 1=1
SQL;
    }

    public function addAdjustment(Adjustment $adjustment)
    {
        return $this->insert($adjustment->toArray());
    }

    public function updateParticipantCredit(Adjustment $adjustment)
    {
        $participant = $adjustment->getParticipant();
        switch ($adjustment->getType()) {
            case 'debit':
                $participant->subtractCredit($adjustment->getAmount());
                break;
            case 'credit':
                $participant->addCredit($adjustment->getAmount());
                break;
        }
        $this->table = 'Participant';
        $result = $this->update($adjustment->getParticipant()->getId(), ['credit' => $participant->getCredit()]);
        $this->table = 'Adjustment';

        return $result;
    }

    public function validate(\Entities\Adjustment $adjustment)
    {
        try {
            //@TODO we have to get products, because products is a private parameter.. this needs
            //to be sorted.
            $amountInCredit = $adjustment->getAmount();
            $oAdjustment = $adjustment->toArray();
            $oAdjustment['amount'] = $amountInCredit;
            $this->getValidator($adjustment)->assert((object) $oAdjustment);
            return true;
        } catch (NestedValidationException $exception) {
            $this->errors = $exception->getMessages();
            return false;
        }
    }

    /**
     * @param Adjustment $adjustment
     * @return Validator
     */
    private function getValidator(Adjustment $adjustment)
    {
        $credit = $adjustment->getParticipant()->getCredit();

        $validator = Validator::attribute('participant_id', Validator::notEmpty()->numeric()->setName('Participant'))
            ->attribute('type', Validator::notEmpty()->numeric()->length(1, 1))
            ->attribute('reference', Validator::optional(Validator::alnum()->length(0, 45)))
            ->attribute('description', Validator::optional(Validator::stringType()->length(0, 255)));

        if ($adjustment->getType() === 'debit') {
            $validator->attribute('amount', Validator::max($credit)->notEmpty()->floatVal());
        } else {
            $validator->attribute('amount', Validator::notEmpty()->floatVal());
        }

        return $validator;
    }
}
