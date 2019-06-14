<?php

namespace Repositories;

use AllDigitalRewards\Services\Catalog\Client;
use AllDigitalRewards\Services\Catalog\Entity\Product;
use Entities\Organization;
use Entities\Address;
use Entities\Transaction;
use Entities\TransactionItem;
use Entities\TransactionMeta;
use Entities\TransactionProduct;
use Entities\Participant;
use Factories\AuthenticationTokenFactory;
use \PDO as PDO;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validator;
use Services\Interfaces\FilterNormalizer;

class TransactionRepository extends BaseRepository
{
    protected $table = 'Transaction';

    private $lastTransactionId;

    private $participant;

    /**
     * @var Client
     */
    private $productCatalog;
    /**
     * @var Client
     */
    private $programProductCatalog;

    public function __construct(PDO $database, Client $productCatalog, Client $programProductCatalog)
    {
        parent::__construct($database);

        $this->productCatalog = $productCatalog;
        $this->programProductCatalog = $programProductCatalog;
    }

    /**
     * @return Client
     */
    public function getProductCatalog(): Client
    {
        return $this->productCatalog;
    }

    /**
     * @param Client $productCatalog
     */
    public function setProductCatalog(Client $productCatalog): void
    {
        $this->productCatalog = $productCatalog;
    }

    /**
     * @return Client
     */
    public function getProgramProductCatalog(): Client
    {
        return $this->programProductCatalog;
    }

    /**
     * @param Client $programProductCatalog
     */
    public function setProgramProductCatalog(Client $programProductCatalog): void
    {
        $this->programProductCatalog = $programProductCatalog;
    }

    public function getRepositoryEntity()
    {
        return Transaction::class;
    }

    /**
     * @param int $id
     * @return Transaction|null
     */
    public function getTransaction(int $id)
    {
        $sql = "SELECT * FROM Transaction WHERE id = ?";
        return $this->query($sql, [$id], Transaction::class);
    }

    public function getCollectionQuery(): string
    {
        return <<<SQL
SELECT Transaction.* 
FROM `Transaction` 
LEFT JOIN Participant ON Participant.id = Transaction.participant_id
WHERE 1=1
SQL;
    }

    public function setParticipant(Participant $participant)
    {
        $this->participant = $participant;
    }

    public function getParticipant(): Participant
    {
        return $this->participant;
    }

    public function getCollection(FilterNormalizer $filters = null, $offset = 30, $limit = 30)
    {
        $collection = parent::getCollection($filters, $offset, $limit); // TODO: Change the autogenerated stub
        foreach ($collection as $index => $c) {
            /** @var Transaction $c */
            $c->setParticipant($this->getParticipant());
            $collection[$index] = $c;
        }

        return $collection;
    }

    private function insertTransactionShipping(Transaction $transaction)
    {
        $this->table = 'Address';
        return parent::insert($transaction->getShipping()->toArray(), true);
    }

    private function insertTransactionProduct(Transaction $transaction)
    {
        $products = $transaction->getProducts();
        foreach ($products as $product) {
            /** @var TransactionProduct $product */

            $this->table = 'TransactionProduct';
            if (!parent::insert($product->toArray(), true)) {
                return false;
            }

            //we need to make a transaction item, and enter this
        }

        return true;
    }

    private function insertTransactionItem(Transaction $transaction)
    {
        $items = $transaction->getItems();
        foreach ($items as $item) {
            /** @var TransactionItem $item */
            $item->setTransactionId($transaction->getId());
            $this->table = 'TransactionItem';
            if (!parent::insert($item->toArray())) {
                return false;
            }
        }

        return true;
    }

    public function getLastInsertId()
    {
        return $this->lastTransactionId; // TODO: Change the autogenerated stub
    }

    public function addTransaction(Transaction $transaction)
    {
        $this->table = 'Transaction';
        $aTransaction = $transaction->toArray();
        if (!parent::insert($aTransaction)) {
            return false;
        }

        $transaction->setId($this->database->lastInsertId());

        if ($transaction->hasShippingAddress() === true) {
            $this->insertTransactionShipping($transaction);
        }

        if ($this->insertTransactionProduct($transaction) &&
            $this->insertTransactionItem($transaction)
        ) {
            $this->lastTransactionId = $transaction->getId();
            return true;
        }
        //@TODO rollback
        return false;
    }

    public function setTransactionMeta($metaCollection)
    {
        $this->table = 'TransactionMeta';
        //@TODO try / catch
        foreach ($metaCollection as $meta) {
            /** @var TransactionMeta $meta */
            if (!$this->place($meta)) {
                return false;
            }
        }
        $this->table = 'Transaction';
    }

    public function getTransactionMeta($transactionId)
    {
        $sql = "SELECT * FROM `TransactionMeta` WHERE transaction_id = ?";
        $args = [$transactionId];
        $sth = $this->database->prepare($sql);
        $sth->execute($args);
        if ($meta = $sth->fetchAll(PDO::FETCH_CLASS, TransactionMeta::class)) {
            return $this->prepareTransactionMeta($meta);
        }

        return [];
    }

    private function prepareTransactionMeta($meta): array
    {
        $associative = [];
        foreach ($meta as $key => $value) {
            $associative[$value->getKey()] = $value->getValue();
        }

        return $associative;
    }

    /**
     * @param array $productContainer
     * @param string|null $program
     * @return Product[]
     */
    public function getProducts($productContainer, $program = null)
    {
        if (empty($productContainer)) {
            return [];
        }

        if ($program === null) {
            return $this->getProductCatalog()->getProducts(['sku' => $productContainer]);
        }

        $products = $this->getProductFromProgramCatalog(['sku' => $productContainer], $program);

        if ($products === false) {
            $products = [];
        }

        if (count($products) !== count($productContainer)) {
            // If a product is not found within the program product criteria
            // we augment it directly from the catalog.
            $productContainer = array_filter(
                $productContainer,
                function ($sku) use ($products) {
                    foreach ($products as $found_product) {
                        /**
                         * @var Product $found_product
                         */
                        if ($found_product->getSku() == $sku) {
                            return false;
                        }
                    }

                    return true;
                }
            );

            $products = array_merge(
                $products,
                $this->getProductCatalog()->getProducts(['sku' => $productContainer])
            );
        }

        return $products;
    }

    private function getProductFromProgramCatalog($sku_container, $program_id)
    {
        $this->getProgramProductCatalog()->setProgram($program_id);
        return $this->getProgramProductCatalog()->getProducts($sku_container);
    }

    public function getParticipantTransaction(Participant $participant, int $transactionId): ?Transaction
    {
        $sql = "SELECT Transaction.*"
            . " FROM `Transaction`"
            . " LEFT JOIN Participant ON Participant.id = Transaction.participant_id"
            . " WHERE Participant.program_id = ? AND Participant.unique_id = ? AND Transaction.id = ?";

        $args = [
            $participant->getProgramId(),
            $participant->getUniqueId(),
            $transactionId
        ];

        if ($transaction = $this->query($sql, $args, Transaction::class)) {
            /** @var Transaction $transaction */
            $transaction->setParticipant($participant);
            //@TODO need a fallback
            if ($transaction->getShippingReference() !== null) {
                $transaction->setShipping(
                    $this->getParticipantTransactionShipping($participant, $transaction->getShippingReference())->toArray()
                );
            }
            $transaction->setWholesale(0);
            $transaction->setSubtotal(0);
            $transaction->setTotal(0);
            $items = $this->getParticipantTransactionProducts($transaction->getId());
            foreach ($items as $item) {
                /** @var TransactionProduct $item */
                $transactionProduct = new TransactionProduct;
                $transactionProduct->exchange($item->toArray());
                $transactionItem = new TransactionItem;
                $transactionItem->setQuantity($item->getQuantity());
                $transactionItem->setTransactionId($transaction->getId());
                $transactionItem->setReferenceId($item->getReferenceId());
                $transactionItem->setGuid($item->getGuid());
                $transaction->setItem($transactionItem, $transactionProduct);
            }
            $transaction->setMeta($this->getTransactionMeta($transactionId));

            return $transaction;
        }

        return null;
    }

    public function getParticipantTransactionItem($guid): ?array
    {
        $sql = <<<SQL
SELECT TransactionItem.quantity, TransactionItem.guid, TransactionItem.transaction_id, TransactionProduct.vendor_code as sku
FROM `TransactionItem`
JOIN TransactionProduct ON TransactionProduct.reference_id = TransactionItem.reference_id
WHERE TransactionItem.guid = ?
SQL;

        $sth = $this->database->prepare($sql);
        $sth->execute([$guid]);
        $sth->setFetchMode(\PDO::FETCH_ASSOC);
        if ($item = $sth->fetch()) {
            return $item;
        }

        return null;
    }

    public function getParticipantTransactions(Participant $participant, $transactionUniqueIds = null): ?array
    {
        $where =  " WHERE Participant.program_id = ? AND Participant.unique_id = ?";
        $params = [$participant->getProgramId(), $participant->getUniqueId()];

        if ($transactionUniqueIds !== null) {
            $placeholder = rtrim(str_repeat('?, ', count($transactionUniqueIds)), ', ');
            $where =  " WHERE Participant.unique_id = ? AND Transaction.unique_id IN ({$placeholder})";
            $params = [$participant->getUniqueId()];

            foreach ($transactionUniqueIds as $transactionUniqueId) {
                $params[] = "$transactionUniqueId";
            }
        }

        $sql = "SELECT Transaction.* FROM `Transaction`"
            . " LEFT JOIN Participant ON Participant.id = Transaction.participant_id"
            . $where;

        $sth = $this->database->prepare($sql);
        $sth->execute($params);

        $transactions = $sth->fetchAll(PDO::FETCH_CLASS, Transaction::class, [$participant]);

        if (empty($transactions)) {
            return [];
        }

        foreach ($transactions as $transaction) {
            $transaction->setMeta($this->getTransactionMeta($transaction->getId()));
        }

        return $transactions;
    }

    private function getParticipantTransactionProducts($transactionId): ?array
    {
        $sql = "SELECT TransactionProduct.*, TransactionItem.quantity, TransactionItem.guid FROM `TransactionItem`"
            . " JOIN TransactionProduct ON TransactionProduct.reference_id = TransactionItem.reference_id"
            . " WHERE TransactionItem.transaction_id = ?";

        $sth = $this->database->prepare($sql);
        $sth->execute([$transactionId]);

        if ($products = $sth->fetchAll(PDO::FETCH_CLASS, TransactionProduct::class)) {
            return $products;
        }

        return null;
    }

    private function getParticipantTransactionShipping(Participant $participant, $reference): ?Address
    {
        $sql = "SELECT * FROM `Address`"
            . " WHERE Address.participant_id = ? AND Address.reference_id = ?";

        $shipping = $this->query($sql, [$participant->getId(), $reference], Address::class);
        if (empty($shipping)) {
            return null;
        }

        return $shipping;
    }

    public function getTransactionOrganization(Transaction $transaction)
    {
        $sql = "SELECT Organization.* FROM `Transaction`"
            . " JOIN Participant ON Participant.id = Transaction.participant_id"
            . " JOIN Program on Program.id = Participant.program_id"
            . " JOIN Organization on Organization.id = Program.organization_Id"
            . " WHERE Transaction.id = ?";

        $sth = $this->database->prepare($sql);
        $sth->execute([$transaction->getId()]);
        $sth->setFetchMode(PDO::FETCH_CLASS, Organization::class);
        $organization = $sth->fetch();

        if (!$organization) {
            throw new \Exception('Transaction does not have an organization.');
        }

        return $organization;
    }

    public function saveTransactionMeta($transactionId, ?array $meta = null)
    {
        if (!is_null($meta)) {
            $metaCollection = [];
            $date = new \DateTime;
            foreach ($meta as $value) {
                $item = new TransactionMeta;
                $key = key($value);
                $item->setKey($key);
                $item->setValue($value[$key]);
                $item->setTransactionId($transactionId);
                $item->setUpdatedAt($date->format('Y-m-d H:i:s'));
                $metaCollection[] = $item;
            }

            $this->setTransactionMeta($metaCollection);
        }
    }

    public function validate(\Entities\Transaction $transaction)
    {
        try {
            //@TODO we have to get products, because products is a private parameter.. this needs to be sorted.

            $this->getValidator()->assert(
                (object)$transaction->toArray()
            );

            if ($transaction->hasShippingAddress()) {
                // Shipping address optional for some products.  Specifically HRA (Sharecare Premium reduction)
                $this->getShippingValidator()->assert((object)$transaction->getShipping()->toArray());
            }
            return true;
        } catch (NestedValidationException $exception) {
            $this->errors = $exception->getMessages();
            return false;
        }
    }

    /**
     * @return Validator
     */
    private function getShippingValidator()
    {
        return Validator::attribute('firstname', Validator::notEmpty()->stringType()->setName('Firstname'))
            ->attribute('lastname', Validator::notEmpty()->stringType()->setName('Lastname'))
            ->attribute('address1', Validator::notEmpty()->stringType()->setName('Address'))
            ->attribute('city', Validator::notEmpty()->stringType()->setName('City'))
            ->attribute('state', Validator::notEmpty()->stringType()->setName('State'))
            ->attribute('zip', Validator::notEmpty()->length(5, 10)->alnum('- ')->setName('Zipcode'));
    }

    /**
     * @return Validator
     */
    private function getValidator()
    {
        return Validator::attribute('participant_id', Validator::notEmpty()->numeric()->setName('Participant'))
            ->attribute('type', Validator::notEmpty()->numeric()->length(1, 1))
            ->attribute('wholesale', Validator::notEmpty()->floatVal())
            ->attribute('subtotal', Validator::notEmpty()->floatVal())
            ->attribute('total', Validator::notEmpty()->floatVal());
    }
}
