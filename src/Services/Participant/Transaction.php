<?php

namespace Services\Participant;

use AllDigitalRewards\AMQP\MessagePublisher;
use AllDigitalRewards\RewardStack\Traits\MetaValidationTrait;
use AllDigitalRewards\Services\Catalog\Entity\Product;
use AllDigitalRewards\Services\Catalog\Entity\InventoryHoldRequest;
use Entities\Adjustment;
use Entities\Event;
use Entities\TransactionItem;
use Entities\TransactionItemReturn;
use Entities\TransactionProduct;
use Entities\User;
use Ramsey\Uuid\Uuid;
use Repositories\BalanceRepository;
use Repositories\TransactionRepository;
use Repositories\ParticipantRepository;
use Services\Participant\Exception\TransactionServiceException;

class Transaction
{
    use MetaValidationTrait;

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
     * @param User|null $user
     * @param array $item
     * @param string|null $notes
     * @return bool
     * @throws \Exception
     */
    public function initiateReturn(?User $user, array $item, ?string $notes): bool
    {
        if ($user === null) {
            throw new \Exception('Unable to find requesting user');
        }
        $guid = $item['guid'];
        $transactionId = $item['transaction_id'];
        $transactionItemId = $item['id'];

        $return = $this->getReturnByGuid($guid);
        if ($return !== null) {
            return true;
        }

        $transactionReturn = new TransactionItemReturn;
        $transactionReturn->setUserId($user->getId());
        $transactionReturn->setTransactionId($transactionId);
        $transactionReturn->setTransactionItemId($transactionItemId);
        $transactionReturn->setNotes($notes);

        // Create return item
        return $this->repository->createTransactionItemReturn($transactionReturn);
    }

    public function getReturnByGuid($guid): ?TransactionItemReturn
    {
        $return = $this->repository->getTransactionItemReturn($guid);
        if ($return === null) {
            return null;
        }

        $item = $this->getSingleItem($guid);
        $return->setItem($item);
        return $return;
    }

    public function getReturnById(int $returnId): ?TransactionItemReturn
    {
        $return = $this->repository->getTransactionItemReturnById($returnId);
        if ($return === null) {
            throw new \Exception('Unable to locate return');
        }

        $item = $this->getSingleItemById($return->getTransactionItemId());
        $return->setItem($item);
        return $return;
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
            $skuContainer,
            $transaction->getParticipant()->getProgram()->getUniqueId()
        );

        if (count($skuContainer) !== count($this->requestedProductContainer)) {
            throw new TransactionServiceException('One or more of the requested products are unavailable.');
        }

        foreach ($this->requestedProductContainer as $requestedProduct) {
            foreach ($products as $product) {
                if (strtoupper($requestedProduct->getSku()) === strtoupper($product['sku'])) {
                    if($requestedProduct->isPriceRanged()) {
                        $sku = $product['sku'];
                        $amount = $product['amount'] ?? null;
                        if ($amount === null) {
                            throw new TransactionServiceException("No amount set for ranged product sku: {$sku}.");
                        }
                        $maxRange = $requestedProduct->getPriceRangedMax() ?? null;
                        $minRange = $requestedProduct->getPriceRangedMin() ?? null;
                        if ($maxRange === null || $minRange === null) {
                            throw new TransactionServiceException("Incorrect range set for ranged product sku: {$sku}.");
                        }
                        if ($amount < $minRange || $amount > $maxRange) {
                            $exception = <<<EXCEPTION
                            Price {$amount} set out of range of min: {$minRange} max: {$maxRange} for sku: $sku
                            EXCEPTION;
                            unset($sku);
                            throw new TransactionServiceException($exception);
                        }
                    }
                    $amount = $product['amount'] ?? null;
                    $quantity = $product['quantity'] ?? 1;
                    $transactionProduct = new TransactionProduct($requestedProduct, $amount);
                    $transactionItem = new TransactionItem;
                    $transactionItem->setGuid((string)Uuid::uuid1());
                    $transactionItem->setQuantity((int)$quantity);
                    $transactionItem->setReferenceId($transactionProduct->getReferenceId());

                    if (!$transactionProduct->isValid() || !$transactionItem->isValid()) {
                        $errors = array_merge(
                            $transactionProduct->getValidationErrors(),
                            $transactionItem->getValidationErrors()
                        );

                        throw new TransactionServiceException(implode(', ', $errors));
                    }

                    // Check inventory
                    if ($requestedProduct->isInventoryManaged()) {
                        $adjustedInventory = $requestedProduct->getInventoryCount() - $quantity;
                        if ($adjustedInventory < 0) {
                            throw new TransactionServiceException(
                                $requestedProduct->getName()
                                . ' (' . $requestedProduct->getSku() . ') has insufficient inventory. You requested a quantity of '
                                . $quantity
                                . ' however, there are only '
                                . $requestedProduct->getInventoryCount()
                                . ' available. Please update your order.'
                            );
                        }

                        $holdRequest = new InventoryHoldRequest([
                            'sku' => $requestedProduct->getSku(),
                            'guid' => $transactionItem->getGuid(),
                            'quantity' => $quantity
                        ]);

                        $catalog = clone $this->getTransactionRepository()->getCatalog();
                        $catalog->setUrl(getenv('CATALOG_URL'));
                        $success = $catalog->createInventoryHold($holdRequest);

                        if ($success === false) {
                            throw new TransactionServiceException(
                                'Unable to obtain inventory hold for product ' . $requestedProduct->getName() . ' (' . $requestedProduct->getSku() . ')'
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
        } catch (TransactionServiceException $e) {
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

        //is TransactionMeta well-formed
        if ($this->hasValidMeta($meta) === false) {
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

            $description = null;
            $activityDate = null;
            $reference = null;

            if ($meta !== null) {
                foreach ($meta as $item) {
                    foreach ($item as $key => $value) {
                        if (strtoupper($key) === 'DESCRIPTION') {
                            $description = $value;
                            continue;
                        }
                        if (strtoupper($key) === 'ACTIVITY_DATE') {
                            $activityDate = $value;
                            continue;
                        }
                        if (strtoupper($key) === 'REFERENCE') {
                            $reference = $value;
                            continue;
                        }
                    }
                }
            }

            if ($issuePoints === true) {
                $this->adjustPoints(
                    $participant,
                    'credit',
                    $transaction->getTotal(),
                    $transaction->getId(),
                    $description,
                    $reference,
                    $activityDate
                );
            }

            //@TODO we should deduct credit first..
            $this->adjustPoints(
                $participant,
                'debit',
                $transaction->getTotal(),
                $transaction->getId(),
                $description,
                $reference,
                $activityDate
            );

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
    ): ?\Entities\Transaction {
    
        $participant = $this
            ->participantRepository
            ->getParticipantByOrganization(
                $organization,
                $uniqueId
            );

        if (is_null($participant)) {
            //@TODO change to api exception ?
            $this->repository->setErrors([
                'Participant does not exist.'
            ]);
            return null;
        }
        unset($data['email_address']);
        return $this->insertParticipantTransaction($participant, $data);
    }

    public function adjustPoints(
        \Entities\Participant $participant,
        $type,
        $total,
        $transactionId = null,
        $description = null,
        $reference = null,
        $completed_at = null,
        $transactionItemId = null
    ): ?Adjustment {
    
        $pointConversion = $participant->getProgram()->getPoint();
        $pointTotal = $total * $pointConversion;
        $adjustment = new Adjustment($participant);
        $adjustment->setType($type);
        $adjustment->setAmount($pointTotal);
        $adjustment->setTransactionId($transactionId);
        $adjustment->setTransactionItemId($transactionItemId);
        $adjustment->setDescription($description);
        $adjustment->setReference($reference);

        if (!is_null($completed_at) && strtotime($completed_at) !== false) {
            // Garantee the date time is in the correct date format without throwing errors.
            $completed_at = date('Y-m-d H:i:s', strtotime($completed_at));
        } else {
            $completed_at = null;
        }
        $adjustment->setCompletedAt($completed_at);

        if ($this->balanceRepository->addAdjustment($adjustment) === true) {
            $adjustment = $this
                ->balanceRepository
                ->getAdjustment(
                    $participant,
                    $this->balanceRepository->getLastInsertId()
                );

            $this->balanceRepository->updateParticipantCredit($adjustment);

            return $adjustment;
        }

        return null;
    }

    public function get(\Entities\Participant $participant, $transactionUniqueIds = null, $year = null)
    {
        return $this->repository->getParticipantTransactions($participant, $transactionUniqueIds, $year);
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

    public function getSingleItem($guid): ?array
    {
        return $this->repository->getParticipantTransactionItem($guid, 'guid');
    }

    public function getSingleItemById(int $id): ?array
    {
        return $this->repository->getParticipantTransactionItem($id, 'id');
    }

    public function updateSingleItemMeta($transactionId, $meta)
    {
        $this->repository->saveTransactionMeta($transactionId, $meta);
    }

    public function setReissueDate($guid, $reissueDate)
    {
        return $this->repository->saveReissueDate($guid, $reissueDate);
    }

    public function getErrors()
    {
        return $this->repository->getErrors();
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
                    'Meta::ILLEGAL_META' => _("Transaction Meta is not valid, please provide valid key:value non-empty pairs.")
                ]
            ]);

            return false;
        }

        return true;
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
}
