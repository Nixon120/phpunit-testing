<?php

namespace Services\Participant;

use AllDigitalRewards\AMQP\MessagePublisher;
use AllDigitalRewards\Services\Catalog\Entity\InventoryApproveRequest;
use AllDigitalRewards\Services\Catalog\Entity\InventoryHoldRequest;
use Entities\Adjustment;
use Entities\Event;
use Entities\TransactionItem;
use Entities\TransactionProduct;
use Ramsey\Uuid\Uuid;
use Repositories\BalanceRepository;
use Repositories\TransactionRepository;
use Repositories\ParticipantRepository;
use Services\Participant\Exception\TransactionServiceException;

class Transaction
{
    /**
     * @var TransactionRepository
     */
    public $repository;

    /**
     * @var ParticipantRepository
     */
    public $participantRepository;

    /**
     * @var BalanceRepository
     */
    public $balanceRepository;
    /**
     * @var MessagePublisher
     */
    private $eventPublisher;

    /**
     * @var array
     */
    private $requestedProductContainer = [];

    public function __construct(
        TransactionRepository $repository,
        ParticipantRepository $participantRepository,
        BalanceRepository $balanceRepository,
        MessagePublisher $eventPublisher
    ) {

        $this->repository = $repository;
        $this->participantRepository = $participantRepository;
        $this->balanceRepository = $balanceRepository;
        $this->eventPublisher = $eventPublisher;
    }

    /**
     * @return TransactionRepository
     */
    public function getTransactionRepository()
    {
        return $this->repository;
    }


    /**
     * @param \Entities\Transaction $transaction
     * @param array $data
     * @throws TransactionServiceException
     */
    private function addTransactionItems(
        \Entities\Transaction $transaction,
        array $data
    ) {
        $products = $data['products'] ?? null;
        $skuContainer = array_column($products, 'sku');
        $this->requestedProductContainer = $this->repository->getProducts(
            $skuContainer
        );

        if (count($skuContainer) !== count($this->requestedProductContainer)) {
            throw new TransactionServiceException('One or more of the requested products are unavailable');
        }

        foreach ($this->requestedProductContainer as $requestedProduct) {
            foreach ($products as $product) {
                if ($requestedProduct->getSku() === $product['sku']) {
                    $amount = $product['amount'] ?? null;
                    $quantity = (int)$product['quantity'] ?? null;

                    $transactionProduct = new TransactionProduct($requestedProduct, $amount);
                    $transactionItem = new TransactionItem;
                    $transactionItem->setGuid((string)Uuid::uuid1());
                    $transactionItem->setQuantity($quantity);
                    $transactionItem->setReferenceId($transactionProduct->getReferenceId());

                    if (!$transactionProduct->isValid() || !$transactionItem->isValid()) {
                        $errors = array_merge($transactionProduct->getValidationErrors(), $transactionItem->getValidationErrors());
                        throw new TransactionServiceException(implode(', ', $errors));
                    }

                    // Check inventory
                    if($requestedProduct->isInventoryManaged()) {
                        $adjustedInventory = $requestedProduct->getInventoryCount() - $quantity;
                        if($adjustedInventory < 0) {
                            throw new TransactionServiceException(
                                $requestedProduct->getName() . ' (' . $requestedProduct->getSku() . ') has insufficient inventory'
                            );
                        }

                        $holdRequest = new InventoryHoldRequest([
                            'sku' => $requestedProduct->getSku(),
                            'guid' => $transactionItem->getGuid(),
                            'quantity' => $quantity
                        ]);

                        $success = $this->getTransactionRepository()->getCatalog()
                            ->createInventoryHold($holdRequest);

                        if($success === false) {
                            throw new TransactionServiceException(
                                'Unable to obtain inventory hold for product '. $requestedProduct->getName(). ' ('. $requestedProduct->getSku() . ')'
                            );
                        }
                    }

                    $transaction->setItem($transactionItem, $transactionProduct);
                }
            }
        }
    }

    public function insertParticipantTransaction(
        \Entities\Participant $participant,
        $data
    ) {
        $shipping = $data['shipping'] ?? null;
        $meta = $data['meta'] ?? null;
        $products = $data['products'] ?? null;
        $uniqueId = $data['unique_id'] ?? null;
        $issuePoints = !empty($data['issue_points']) && $data['issue_points'] === true ? true : false;
        if ($products === null) {
            //@TODO change to api exception ?
            $this->repository->setErrors([
                'No Products were included in transaction.'
            ]);
            return null;
        }

        //@TODO let's make a transaction service/repository to pass around?
        $transaction = new \Entities\Transaction($participant);

        try {
            $this->addTransactionItems($transaction, $data);
        }catch (TransactionServiceException $e) {
            $this->repository->setErrors([
                $e->getMessage()
            ]);
            return null;
        }

        if ($shipping !== null) {
            $transaction->setShipping($shipping);
        }
        $transaction->setType(1);
        $transaction->setEmailAddress($participant->getEmailAddress());
        $transaction->setUniqueId($uniqueId);
        $credit = $participant->getCredit();
        $pointTotal = $transaction->getPointTotal();
        if ($credit < $pointTotal && $issuePoints === false) {
            $this->repository->setErrors([
                'Participant does not have enough points for this transaction.'
            ]);
            return null;
        }

        if ($this->repository->validate($transaction)
            && $this->repository->addTransaction($transaction)
        ) {
            $transactionId = $this->repository->getLastInsertId();
            $this->repository->saveTransactionMeta($transactionId, $meta);
            $transaction = $this
                ->repository
                ->getParticipantTransaction(
                    $participant,
                    $transactionId
                );

            if ($issuePoints === true) {
                $this->adjustPoints(
                    $participant,
                    'credit',
                    $transaction->getTotal(),
                    $transaction->getId()
                );

                $this->queueAdjustmentEvent('credit', $participant->getId());
            }

            //@TODO we should deduct credit first..
            $this->adjustPoints(
                $participant,
                'debit',
                $transaction->getTotal(),
                $transaction->getId()
            );

            $this->queueAdjustmentEvent('debit', $participant->getId());

            // We'll approve the inventory hold through the Transaction.create webhook listener event
            $this->queueEvent($transactionId);

            return $transaction;
        }

        return null;
    }

    public function insert(
        $organization,
        $uniqueId,
        $data
    ):?\Entities\Transaction {
        $participant = $this
            ->participantRepository
            ->getParticipantByOrganization(
                $organization,
                $uniqueId
            );

        if (is_null($participant)) {
            //@TODO change to api exception ?
            return null;
        }
        unset($data['email_address']);
        return $this->insertParticipantTransaction($participant, $data);
    }

    private function adjustPoints(
        \Entities\Participant $participant,
        $type,
        $total,
        $transactionId = null
    ) {

        $pointConversion = $participant->getProgram()->getPoint();
        $pointTotal = $total * $pointConversion;
        $adjustment = new Adjustment($participant);
        $adjustment->setType($type);
        $adjustment->setAmount($pointTotal);
        $adjustment->setTransactionId($transactionId);
        if ($this->balanceRepository->addAdjustment($adjustment)) {
            $adjustment = $this
                ->balanceRepository
                ->getAdjustment(
                    $participant,
                    $this->balanceRepository->getLastInsertId()
                );

            $this->balanceRepository->updateParticipantCredit($adjustment);

            return $adjustment;
        }
    }

    public function get(\Entities\Participant $participant)
    {
        return $this->repository->getParticipantTransactions($participant);
    }

    public function getTransactionOrganization(
        \Entities\Transaction $transaction
    ) {

        return $this
            ->repository
            ->getTransactionOrganization($transaction);
    }

    public function getSingle(\Entities\Participant $participant, $transactionId)
    {
        return $this->repository->getParticipantTransaction($participant, $transactionId);
    }

    public function getSingleItem($guid)
    {
        return $this->repository->getParticipantTransactionItem($guid);
    }

    public function getErrors()
    {
        return $this->repository->getErrors();
    }

    protected function queueEvent($id)
    {
        $event = new Event();
        $event->setName('Transaction.create');
        $event->setEntityId($id);
        $this
            ->eventPublisher
            ->publish(json_encode($event));
    }

    protected function queueAdjustmentEvent($type, $id)
    {
        $event = new Event();
        $event->setName('Adjustment.' . $type);
        $event->setEntityId($id);
        $this
            ->eventPublisher
            ->publish(json_encode($event));
    }
}
