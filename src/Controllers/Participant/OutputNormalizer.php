<?php

namespace Controllers\Participant;

use AllDigitalRewards\StatusEnum\StatusEnum;
use AllDigitalRewards\UserAccessLevelEnum\UserAccessLevelEnum;
use Controllers\AbstractOutputNormalizer;
use Entities\Address;
use Entities\TransactionItem;
use Entities\Participant;

class OutputNormalizer extends AbstractOutputNormalizer
{
    private $userAccessLevel = null;

    /**
     * @return mixed
     */
    public function getUserAccessLevel()
    {
        if ($this->userAccessLevel === null) {
            $this->userAccessLevel = (new UserAccessLevelEnum())->hydrateLevel(StatusEnum::ACTIVE, true);
        }
        return $this->userAccessLevel;
    }

    /**
     * @param mixed $userAccessLevel
     */
    public function setUserAccessLevel($userAccessLevel): void
    {
        $this->userAccessLevel = $userAccessLevel;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    private function userHasPiiAccess(): bool
    {
        return !(new UserAccessLevelEnum())->isPiiLimited($this->getUserAccessLevel());
    }

    private function prepareAddressOutput(?Address $address)
    {
        if ($address !== null) {
            $address = $this->scrub($address->toArray(), [
                'id',
                'participant_id',
                'reference_id'
            ]);

            if ($this->userHasPiiAccess() === false) {
                $address['firstname'] = '';
                $address['lastname'] = '';
                $address['address1'] = '';
                $address['address2'] = '';
                $address['city'] = '';
                $address['state'] = '';
                $address['zip'] = '';
            }
        }

        return $address;
    }

    public function get(): array
    {
        /** @var Participant $participant */
        $participant = parent::get();
        $return = $participant->toArray();
        $return['address'] = $this->prepareAddressOutput($participant->getAddress());
        $return['program'] = $participant->getProgram()->getUniqueId();
        $return['organization'] = $participant->getOrganization()->getUniqueId();
        $return['meta'] = $participant->getMeta();
        $return['firstname'] = $participant->getFirstname();
        $return['lastname'] = $participant->getLastname();
        $return['status'] = $participant->getStatus();

        $return = $this->scrub($return, [
            'address_reference',
            'password',
            'sso',
            'id',
            'organization_id',
            'program_id',
        ]);

        $return = $this->getAccessiblePiiResponse($return);

        return $return;
    }

    public function getAdjustment(): array
    {
        /** @var \Entities\Adjustment $adjustment */
        $adjustment = parent::get();
        $return = $adjustment->toArray();
        $return['amount'] = $adjustment->getAmount();
        $return['type'] = $adjustment->getType();
        $return = $this->scrub($return, [
            'participant_id',
            'active'
        ]);

        $participant = $adjustment->getParticipant();
        $participantReturn = $participant->toArray();
        $participantReturn['address'] = $this->prepareAddressOutput($participant->getAddress());
        $participantReturn['program'] = $participant->getProgram()->getUniqueId();
        $participantReturn['program_points'] = $participant->getProgram()->getPoint();
        $participantReturn['organization'] = $participant->getOrganization()->getUniqueId();
        $participantReturn['meta'] = $participant->getMeta();
        $participantReturn['firstname'] = $participant->getFirstname();
        $participantReturn['lastname'] = $participant->getLastname();

        $participantReturn = $this->scrub($participantReturn, [
            'address_reference',
            'password',
            'sso',
            'id',
            'organization_id',
            'program_id'
        ]);
        $participantReturn['meta'] = $participant->getMeta();

        $participantReturn = $this->getAccessiblePiiResponse($participantReturn);

        $return['user'] = $participantReturn;

        return $return;
    }

    public function getAdjustmentList(Participant $participant): array
    {
        $list = parent::get();

        $return = $this->scrubList($list, [
            'participant_id',
            'active'
        ]);

        foreach ($return as $k => $v) {
            $return[$k]['amount'] = bcmul($return[$k]['amount'], $participant->getProgram()->getPoint(), 2);
        }

        return $return;
    }

    public function getTransaction(): array
    {
        /** @var \Entities\Transaction $transaction */
        $transaction = parent::get();
        $participant = $transaction->getParticipant();
        $return = $transaction->toArray();
        $return['points'] = bcmul($transaction->getTotal(), $participant->getProgram()->getPoint(), 2);
        $return['shipping'] = $this->prepareAddressOutput($transaction->getShipping());
        $return = $this->getAccessiblePiiResponse($return, true);

        $return['products'] = [];
        $items = $transaction->getItems();
        foreach ($items as $item) {
            /** @var TransactionItem $item */
            $product = $transaction->getProduct($item->getReferenceId());
            $total = bcmul($product->getPrice(), $item->getQuantity(), 2);
            $points = bcmul($total, $participant->getProgram()->getPoint(), 2);
            $return['products'][] = [
                'name' => $product->getName(),
                'sku' => $product->getUniqueId(),
                'quantity' => $item->getQuantity(),
                'total' => $total,
                'points' => $points,
                'guid' => $item->getGuid(),
                'reissue_date' => $item->getReissueDate(),
                'returned' => $item->isReturned()
            ];
        }

        $participantReturn = $participant->toArray();
        $participantReturn['address'] = $this->prepareAddressOutput($participant->getAddress());
        $participantReturn['program'] = $participant->getProgram()->getUniqueId();
        $participantReturn['organization'] = $participant->getOrganization()->getUniqueId();
        $participantReturn['meta'] = $participant->getMeta();
        $participantReturn['firstname'] = $participant->getFirstname();
        $participantReturn['lastname'] = $participant->getLastname();

        $participantReturn = $this->scrub($participantReturn, [
            'address_reference',
            'password',
            'sso',
            'id',
            'organization_id',
            'program_id'
        ]);
        //@TODO Change to participant in v2
        $participantReturn = $this->getAccessiblePiiResponse($participantReturn);

        $return['user'] = $participantReturn;

        $return['meta'] = $transaction->getMeta();
        $return = $this->scrub($return, [
            'participant_id',
            'verified',
            'completed',
            'processed',
            'notes',
            'shipping_reference',
            'active',
            'bypass_conditions'
        ]);

        return $return;
    }

    public function getTransactionList(): array
    {
        /** @var \Entities\Transaction[] $list */
        $list = parent::get();

        $meta = [];
        $products = [];
        $programPoint = $list[0]->getParticipant()->getProgram()->getPoint();

        foreach ($list as $key => $transaction) {
            $meta[] = $transaction->getMeta();
            $items = $transaction->getItems();

            foreach ($items as $item) {
                /** @var TransactionItem $item */
                $product = $transaction->getProduct($item->getReferenceId());
                $total = bcmul($product->getPrice(), $item->getQuantity(), 2);
                $points = bcmul($total, $programPoint, 2);
                $products[$key][] = [
                    'name' => $product->getName(),
                    'sku' => $product->getUniqueId(),
                    'quantity' => $item->getQuantity(),
                    'total' => $total,
                    'points' => $points,
                    'guid' => $item->getGuid(),
                    'reissue_date' => $item->getReissueDate()
                ];
            }
        }

        $return = $this->scrubList($list, [
            'participant_id',
            'verified',
            'completed',
            'processed',
            'notes',
            'shipping_reference',
            'active',
            'bypass_conditions'
        ]);

        $transactions = [];
        foreach ($return as $key => $transaction) {
            $transactions[$key] = $transaction;
            $transactions[$key]['meta'] = $meta[$key];
            $transactions[$key]['products'] = $products[$key];
            $transactions[$key] = $this->getAccessiblePiiResponse($transactions[$key], true);
        }

        return $transactions;
    }

    public function getList(): array
    {
        $list = parent::get();

        $return = $this->scrubList($list, [
            'sso',
            'address_reference',
            'meta',
            'id',
            'organization_id',
            'program_id',
            'birthdate'
        ]);

        foreach ($return as $key => $participant) {
            $return[$key]['program'] = $participant['program_reference'];
            $return[$key]['organization'] = $participant['organization_reference'];
            unset($return[$key]['organization_reference'], $return[$key]['program_reference']);

            $return[$key] = $this->getAccessiblePiiResponse($return[$key]);
        }

        return $return;
    }

    public function getItem(): array
    {
        /** @var \Entities\TransactionProduct $item */
        $item = parent::get();
        return [
            'sku' => $item['sku'],
            'quantity' => $item['quantity'],
            'guid' => $item['guid'],
            'transaction_id' => $item['transaction_id']
        ];
    }

    /**
     * @param array $participantReturn
     * @param bool $scrubEmailOnly
     * @return array
     * @throws \Exception
     */
    private function getAccessiblePiiResponse(array $participantReturn, bool $scrubEmailOnly = false): array
    {
        if ($this->userHasPiiAccess() === false) {
            if ($scrubEmailOnly === true) {
                $participantReturn['email_address'] = '';
                return $participantReturn;
            }
            $participantReturn['firstname'] = '';
            $participantReturn['lastname'] = '';
            $participantReturn['email_address'] = '';
            $participantReturn['phone'] = '';
            $participantReturn['birthdate'] = null;
        }
        return $participantReturn;
    }
}
