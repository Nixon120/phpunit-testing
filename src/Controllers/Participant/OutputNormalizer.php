<?php

namespace Controllers\Participant;

use Controllers\AbstractOutputNormalizer;
use Entities\Address;
use Entities\TransactionItem;
use Entities\TransactionProduct;
use Entities\Participant;

class OutputNormalizer extends AbstractOutputNormalizer
{
    private function prepareAddressOutput(?Address $address)
    {
        if ($address !== null) {
            $address = $this->scrub($address->toArray(), [
                'id',
                'participant_id',
                'reference_id'
            ]);
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
        $return = $this->scrub($return, [
            'address_reference',
            'password',
            'sso',
            'id',
            'organization_id',
            'program_id'
        ]);
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
        $return['points'] = bcmul($transaction->getTotal(), $participant->getProgram()->getPoint());
        $return['shipping'] = $this->prepareAddressOutput($transaction->getShipping());
        $return['total'] = bcmul($return['total'], $participant->getProgram()->getPoint(), 2);
        $return['products'] = [];
        $items = $transaction->getItems();
        foreach ($items as $item) {
            /** @var TransactionItem $item */
            $product = $transaction->getProduct($item->getReferenceId());
            $total = bcmul($product->getPrice(), $item->getQuantity(), 2);
            $total = bcmul($total, $participant->getProgram()->getPoint(), 2);
            $points = bcmul($total, $participant->getProgram()->getPoint());
            $return['products'][] = [
                'name' => $product->getName(),
                'sku' => $product->getUniqueId(),
                'quantity' => $item->getQuantity(),
                'total' => $total,
                'points' => $points,
                'guid' => $item->getGuid()
            ];
        }

        $participantReturn = $participant->toArray();
        $participantReturn['address'] = $this->prepareAddressOutput($participant->getAddress());
        $participantReturn['program'] = $participant->getProgram()->getUniqueId();
        $participantReturn['organization'] = $participant->getOrganization()->getUniqueId();
        $participantReturn['meta'] = $participant->getMeta();
        $participantReturn = $this->scrub($participantReturn, [
            'address_reference',
            'password',
            'sso',
            'id',
            'organization_id',
            'program_id'
        ]);
        //@TODO Change to participant in v2
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

    public function getTransactionList(Participant $participant): array
    {
        $list = parent::get();

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

        foreach ($return as $k => $v) {
           $return[$k]['total'] = bcmul($return[$k]['total'], $participant->getProgram()->getPoint(), 2);
        }
        return $return;
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
}
