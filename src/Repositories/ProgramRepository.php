<?php

namespace Repositories;

use AllDigitalRewards\Services\Catalog\Client;
use Entities\Adjustment;
use Entities\AutoRedemption;
use Entities\Contact;
use Entities\Domain;
use Entities\Faqs;
use Entities\FeaturedProduct;
use Entities\LayoutRow;
use Entities\LayoutRowCard;
use Entities\OfflineRedemption;
use Entities\OneTimeAutoRedemption;
use Entities\Organization;
use Entities\Participant;
use Entities\ProductCriteria;
use Entities\Program;
use Entities\ProgramType;
use Entities\Sweepstake;
use Entities\SweepstakeDraw;
use Entities\Transaction;
use League\Flysystem\Filesystem;
use \PDO as PDO;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validator;

class ProgramRepository extends BaseRepository
{
    protected $table = 'Program';

    /**
     * @var Client
     */
    private $catalog;

    /**
     * @var Filesystem
     */
    private $filesystem;
    /**
     * @var bool
     */
    private $isClone = false;

    public function __construct(PDO $database, Client $client, Filesystem $filesystem)
    {
        parent::__construct($database);
        $this->catalog = $client;
        $this->filesystem = $filesystem;
    }

    private function getFilesystem()
    {
        return $this->filesystem;
    }

    /**
     * @return bool
     */
    public function isClone(): bool
    {
        return $this->isClone;
    }

    /**
     * @param bool $isClone
     */
    public function setIsClone(bool $isClone): void
    {
        $this->isClone = $isClone;
    }


    public function getRepositoryEntity()
    {
        return Program::class;
    }

    public function getCollectionQuery(): string
    {
        $where = " WHERE 1 = 1 ";
        if (!empty($this->getOrganizationIdContainer())) {
            $organizationIdString = implode(',', $this->getOrganizationIdContainer());
            $where = <<<SQL
WHERE Program.organization_id IN ({$organizationIdString})
SQL;
        }

        $sql = <<<SQL
SELECT Program.id, Program.unique_id, Program.organization_id, Program.name, Program.point, Program.url, 
Program.domain_id, Program.meta, Program.active, Program.updated_at, Program.created_at, Program.published, 
Organization.unique_id AS organization_reference 
FROM Program 
JOIN Organization ON Program.organization_id = Organization.id
LEFT JOIN Domain ON Domain.id = Program.domain_id 
{$where}
SQL;

        return $sql;
    }

    //@TODO change this to getByUnique or getById
    public function getProgram($id, $uniqueLookup = true): ?Program
    {
        $field = $uniqueLookup === false ? 'id' : 'unique_id';
        $sql = "SELECT * FROM Program WHERE {$field} = ?";

        if (!empty($this->getOrganizationIdContainer())) {
            $organizationIdString = implode(',', $this->getOrganizationIdContainer());
            $sql .= <<<SQL
 AND Program.organization_id IN ({$organizationIdString});
SQL;
        }

        $args = [$id];

        if (!$program = $this->query($sql, $args, Program::class)) {
            return null;
        }

        return $this->hydrateProgram($program);
    }

    public function getCreditAdjustmentsByMeta($input)
    {
        $page = $input['page'] ?? 1;
        $limit = $input['limit'] ?? 30;
        $offset = $page === 1 ? 0 : ($page - 1) * $limit;
        $paginationSql = "LIMIT {$limit} OFFSET {$offset}";
        $key = $input['meta_key'];
        $value = $input['meta_value'];
        $program = $input['program'];

        $sql = <<<SQL
SELECT *
FROM Adjustment
WHERE Adjustment.participant_id IN (
  SELECT ParticipantMeta.participant_id
  FROM ParticipantMeta
  LEFT JOIN Participant ON Participant.id = ParticipantMeta.participant_id
  WHERE ParticipantMeta.`key` = '$key' AND ParticipantMeta.value = '$value'
   AND Participant.program_id = $program
)
AND Adjustment.reference IS NOT NULL 
{$paginationSql}
SQL;

        $sth = $this->database->prepare($sql);
        $sth->execute();

        return $sth->fetchAll(\PDO::FETCH_CLASS, Adjustment::class);
    }

    private function hydrateProgram(Program $program)
    {
        $program->setOrganization($this->getProgramOrganization($program->getOrganizationId()));
        $domain = $this->getProgramDomain($program->getDomainId());
        $program->setDomain($domain);
        $program->setAutoRedemption($this->getAutoRedemption($program));
        $program->setOneTimeAutoRedemptions($this->getOneTimeAutoRedemptions($program));
        $program->setContact($this->getContact($program));
        $program->setAccountingContact($this->getAccountingContact($program));
        $program->setProductCriteria($this->getProductCriteria($program));
        $program->setLayoutRows($this->getProgramLayout($program));
        $program->setSweepstake($this->getProgramSweepstake($program));
        $program->setFeaturedProducts($this->getProgramFeaturedProducts($program));
        $program->setProgramTypes($this->getProgramTypes($program));
        if ($program->getEndDate() !== null) {
            $timezone = $program->getTimezone() ?? 'America/Phoenix';
            $time = new \DateTime($program->getEndDate(), new \DateTimeZone("UTC"));
            $time->setTimezone(new \DateTimeZone($timezone));
            $program->setEndDate($time->format('Y-m-d H:i:s'));
        }
        return $program;
    }

    private function getProgramTypes(Program $program)
    {
        $sql = <<<SQL
SELECT * 
FROM ProgramType
WHERE id IN (SELECT program_type_id FROM ProgramToProgramType WHERE ProgramToProgramType.program_id = ?)
SQL;

        $args = [$program->getId()];

        $sth = $this->database->prepare($sql);
        $sth->execute($args);
        $types = $sth->fetchAll(PDO::FETCH_CLASS, ProgramType::class);
        if (empty($types)) {
            $types = [];
        }

        return $types;
    }

    private function getProgramFeaturedProducts(Program $program)
    {
        $sql = "SELECT * FROM `FeaturedProduct` WHERE program_id = ? ORDER BY id ASC";
        $args = [$program->getUniqueId()];

        $sth = $this->database->prepare($sql);
        $sth->execute($args);
        $featured = $sth->fetchAll(PDO::FETCH_CLASS, FeaturedProduct::class);
        if (empty($featured)) {
            return [];
        }

        return $featured;
    }

    private function splitDomain($domain)
    {
        $domain = parse_url($domain);
        if (count($domain) !== 1) {
            //malformed URL
            return false;
        }
        $path = explode('.', $domain['path']);
        $url = $path[0];
        unset($path[0]);
        return (object)[
            'url' => $url,
            'domain' => implode('.', $path)
        ];
    }

    public function getProgramByDomain(string $domain): ?Program
    {
        $domainParts = $this->splitDomain($domain);

        if (!$domain = $this->getProgramDomainByDomainName($domainParts->domain)) {
            return null;
        }

        $sql = "SELECT * FROM Program WHERE url = ? AND domain_id = ?";
        $args = [$domainParts->url, $domain->getId()];

        if (!$program = $this->query($sql, $args, Program::class)) {
            return null;
        }

        return $this->hydrateProgram($program);
    }

    public function getProgramByDomainId(int $id): ?Program
    {
        $sql = "SELECT * FROM Program WHERE domain_id = ?";
        $args = [$id];

        if (!$program = $this->query($sql, $args, Program::class)) {
            return null;
        }

        return $this->hydrateProgram($program);
    }

    public function getProgramOrganization(?string $id, $unique = false): ?Organization
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

    public function getProgramDomain(?string $id): ?Domain
    {
        $sql = "SELECT * FROM `Domain` WHERE id = ?";
        $args = [$id];
        return $this->query($sql, $args, Domain::class);
    }

    public function getProgramDomainByDomainName(string $domain): ?Domain
    {
        $sql = "SELECT * FROM `Domain` WHERE url = ?";
        $args = [$domain];
        return $this->query($sql, $args, Domain::class);
    }

    public function getAutoRedemption(Program $program): ?AutoRedemption
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

    public function getOneTimeAutoRedemptions(Program $program)
    {
        $sql = "SELECT * FROM `OneTimeAutoRedemption` WHERE program_id = ?";
        $args = [$program->getUniqueId()];
        $sth = $this->database->prepare($sql);
        $sth->execute($args);

        $oneTimeAutoRedemptions = $sth->fetchAll(PDO::FETCH_CLASS, OneTimeAutoRedemption::class);
        if (empty($oneTimeAutoRedemptions)) {
            return [];
        }

        return $oneTimeAutoRedemptions;
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

        //fetch/set products with approved skus
        $skus = json_decode($offlineRedemptions[0]->getSkus());
        $products = $this->getOfflineRedemptionProducts($skus);
        $offlineRedemptions[0]->setSkus($products);

        return $offlineRedemptions;
    }

    private function getOfflineRedemptionProducts($products)
    {
        $return = [];
        if (!empty($products)) {
            $skuContainer = ['sku' => $products];
            $vendorProducts = $this->catalog->getProducts($skuContainer);
            foreach ($vendorProducts as $product) {
                if (in_array($product->getSku(), $products)) {
                    $return[] = $product;
                }
            }
        }

        return $return;
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

    public function getAccountingContact(Program $program)
    {
        $sql = "SELECT * FROM `Contact` WHERE reference_id = ?";
        $args = [$program->getAccountingContactReference()];

        if (!$contact = $this->query($sql, $args, Contact::class)) {
            return null;
        }

        return $contact;
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

    /**
     * @param int $programId
     * @param ProgramType[] $programTypes
     * @return bool
     */
    public function placeProgramTypes(int $programId, array $programTypes): bool
    {
        $this->table = 'ProgramToProgramType';

        $sql = <<<SQL
DELETE FROM ProgramToProgramType
WHERE program_id = ?
SQL;

        $sth = $this->getDatabase()->prepare($sql);
        $sth->execute([$programId]);

        foreach ($programTypes as $type) {
            $this->insert([
                'program_id' => $programId,
                'program_type_id' => $type->getId()
            ]);
        }
        $this->table = 'Program';

        return true;
    }

    public function placeSettings(AutoRedemption $settings): bool
    {
        $this->table = 'AutoRedemption';
        $result = $this->place($settings);
        $this->table = 'Program';

        return $result;
    }

    public function isDomainDeletable($domainId)
    {
        $exists = $this->getProgramByDomainId($domainId);

        if (is_null($exists)) {
            return true;
        }

        $error = 'Unable to delete this Domain. It is assigned to a Program.';
        array_push($this->errors, $error);
        return false;
    }

    public function isProgramIdUnique($uniqueId)
    {
        $exists = $this->getProgram($uniqueId);

        if (is_null($exists) || $exists->getUniqueId() !== $uniqueId) {
            return true;
        }

        $error = 'Program ID ' . $uniqueId . ' has already been assigned to another Program.';
        array_push($this->errors, $error);
        return false;
    }

    private function getCategories($categories)
    {
        $return = [];
        if (!empty($categories)) {
            $vendorCategories = $this->catalog->getCategories();
            foreach ($vendorCategories as $category) {
                if (in_array($category->getUniqueId(), $categories)) {
                    $return[] = $category;
                }
            }
        }

        return $return;
    }

    private function getBrands($brands)
    {
        $return = [];
        if (!empty($brands)) {
            $vendorBrands = $this->catalog->getBrands();
            foreach ($vendorBrands as $brand) {
                if (in_array($brand->getUniqueId(), $brands)) {
                    $return[] = $brand;
                }
            }
        }

        return $return;
    }

    private function getExcludedBrands($brands)
    {
        $return = [];
        if (!empty($brands)) {
            $vendorBrands = $this->catalog->getBrands();
            foreach ($vendorBrands as $brand) {
                if (in_array($brand->getUniqueId(), $brands)) {
                    $return[] = $brand;
                }
            }
        }

        return $return;
    }

    private function getExcludedVendors($vendors)
    {
        $return = [];
        if (!empty($vendors)) {
            $vendorList = $this->catalog->getVendors();
            foreach ($vendorList as $item) {
                if (in_array($item->getUniqueId(), $vendors)) {
                    $return[] = $item->getUniqueId();
                }
            }
        }

        return $return;
    }

    private function getProducts($products)
    {
        $return = [];

        if (!empty($products)) {
            foreach (array_chunk($products, 50) as $skuSet) {
                $skuContainer = ['sku' => $skuSet];
                $vendorProducts = $this->catalog->getProducts($skuContainer);
                foreach ($vendorProducts as $product) {
                    if (in_array($product->getSku(), $products)) {
                        $return[] = $product;
                    }
                }
            }
        }

        return $return;
    }

    private function getExcludedProducts($products)
    {
        $return = [];
        if (!empty($products)) {
            $skuContainer = ['sku' => $products];
            $vendorProducts = $this->catalog->getProducts($skuContainer);
            foreach ($vendorProducts as $product) {
                if (in_array($product->getSku(), $products)) {
                    $return[] = $product;
                }
            }
        }

        return $return;
    }

    /**
     * @param string $fromId
     * @param string $toId
     * @return bool
     */
    public function cloneProductCriteria(string $fromId, string $toId): bool
    {
        $sql = <<<SQL
INSERT INTO `ProductCriteria`(program_id, filter, created_at, updated_at, featured_page_title)
SELECT ?, filter, NOW(), NOW(), featured_page_title FROM `ProductCriteria` WHERE program_id = ?;
SQL;

        $args = [$toId, $fromId];

        $sth = $this->database->prepare($sql);
        $updated = $sth->execute($args);
        if ($updated === true) {
            $sql = <<<SQL
INSERT INTO `FeaturedProduct`(program_id, sku, created_at, updated_at)
SELECT ?, sku, NOW(), NOW() FROM `FeaturedProduct` WHERE program_id = ?;
SQL;
            $sth = $this->database->prepare($sql);
            return $sth->execute($args);
        }

        return $updated;
    }

    public function getProductCriteria(Program $program): ?ProductCriteria
    {
        $sql = "SELECT * FROM `ProductCriteria` WHERE program_id = ?";
        $args = [$program->getUniqueId()];
        if (!$criteria = $this->query($sql, $args, ProductCriteria::class)) {
            return null;
        }

        return $this->hydrateFilterElements($criteria);
    }

    private function hydrateFilterElements(ProductCriteria $criteria)
    {
        // force hydration.. maybe look at a different approach. This is confusing
        $criteria->setFilter($criteria->getFilter());
        $criteria->setFeaturedPageTitle($criteria->getFeaturedPageTitle());
        $criteria->setCategories($this->getCategories($criteria->getCategoryFilter()));
        $criteria->setBrands($this->getBrands($criteria->getBrandFilter()));
        $criteria->setProducts($this->getProducts($criteria->getProductFilter()));
        $criteria->setExcludeProducts($this->getExcludedProducts($criteria->getExcludeProductsFilter()));
        $criteria->setExcludeBrands($this->getExcludedBrands($criteria->getExcludeBrandsFilter()));
        $criteria->setExcludeVendors($this->getExcludedVendors($criteria->getExcludeVendorsFilter()));
        return $criteria;
    }

    public function validate(\Entities\Program $program)
    {
        try {
            $oProgram = (object)$program->toArray();

            $autoRedemption = $program->getAutoRedemption() ?? null;

            $this->getValidator($program)->assert($oProgram);

            if ($autoRedemption !== null && $autoRedemption->isActive()) {
                $this->getRedemptionValidator()->assert((object)$autoRedemption->toArray());
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
    private function getValidator(Program $program)
    {
        $validator = Validator::attribute('name', Validator::notEmpty()->setName('Name'))
            ->attribute('point', Validator::numeric()->min(1)->setName('Point'))
            ->attribute('unique_id', Validator::notEmpty()->alnum('_ -')->noWhitespace()->setName('Unique Id'))
            ->attribute('organization_id', Validator::notEmpty()->setName('Organization'))
            ->attribute('deposit_amount', Validator::optional(Validator::numeric()->setName('Deposit')))
            ->attribute('low_level_deposit', Validator::optional(Validator::numeric()->setName('LowLevelDeposit')))
            ->attribute(
                'timezone',
                Validator::optional(Validator::stringType()->setName('TimeZone'))
            )
            ->attribute(
                'cost_center_id',
                Validator::optional(Validator::notEmpty()->length(1, 45)->setName('Cost Center'))
            );

        return $validator;
    }

    /**
     * @return Validator
     */
    private function getRedemptionValidator()
    {
        return Validator::attribute('product_sku', Validator::notEmpty()->setName('Product'));
    }

    public function saveFeaturedProducts(Program $program, array $productSkuContainer, string $featuredPageTitle): bool
    {
        $this->table = 'FeaturedProduct';

        $this->deleteAllProgramFeaturedProducts($program);

        if (!empty($productSkuContainer)) {
            foreach ($productSkuContainer as $sku) {
                $product = new FeaturedProduct;
                $product->setProgramId($program->getUniqueId());
                $product->setSku($sku);
                $this->place($product);
            }
            $this->table = 'Program';
        }

        $sql = "SELECT * FROM `ProductCriteria` WHERE program_id = ?";
        $sth = $this->database->prepare($sql);
        $sth->execute([$program->getUniqueId()]);
        $result = $sth->fetchAll();

        $sql = "INSERT INTO `ProductCriteria` (featured_page_title, program_id, filter) VALUES (?, ?, '')";
        if (empty($result) === false) {
            $sql = "UPDATE `ProductCriteria` SET featured_page_title = ?, updated_at = NOW() WHERE program_id = ?";
        }

        $args = [$featuredPageTitle, $program->getUniqueId()];
        $sth = $this->database->prepare($sql);

        return $sth->execute($args);
    }

    private function deleteAllProgramFeaturedProducts(Program $program)
    {
        $sql = <<<SQL
DELETE FROM FeaturedProduct WHERE FeaturedProduct.program_id = '{$program->getUniqueId()}'
SQL;
        $sth = $this->getDatabase()->prepare($sql);
        return $sth->execute();
    }

    public function saveProductCriteria(Program $program, $filterData): bool
    {
        $featuredPageTitle = '';
        if ($this->getProductCriteria($program) !== null) {
            $featuredPageTitle = $this->getProductCriteria($program)->getFeaturedPageTitle();
        }
        $criteria = new ProductCriteria;
        $criteria->setFilter($filterData);
        $criteria->setFeaturedPageTitle($featuredPageTitle);
        $criteria->setProgramId($program->getUniqueId());
        return $this->placeProductCriteria($criteria);
    }

    public function placeProductCriteria(?ProductCriteria $criteria): bool
    {
        $this->table = 'ProductCriteria';
        $result = $this->place($criteria);
        $this->table = 'Program';

        return $result;
    }

    /**
     * @param Program $program
     * @return LayoutRow[]
     */
    public function getProgramLayout(Program $program)
    {
        $sql = "SELECT * FROM `LayoutRow` WHERE program_id = ?";
        $args = [$program->getUniqueId()];
        $sth = $this->database->prepare($sql);
        $sth->execute($args);
        $layout = $sth->fetchAll(PDO::FETCH_CLASS, LayoutRow::class);
        if (!$layout) {
            return null;
        }

        return $this->hydrateLayoutRowContainer($layout);
    }

    /**
     * @param Program $program
     * @return Faqs[]
     */
    public function getProgramFaqs(Program $program)
    {
        $sql = "SELECT * FROM `Faqs` WHERE program_id = ?";
        $args = [$program->getUniqueId()];
        $sth = $this->database->prepare($sql);
        $sth->execute($args);
        $faqs = $sth->fetchAll(PDO::FETCH_CLASS, Faqs::class);
        if (!$faqs) {
            return null;
        }

        return $faqs;
    }

    /**
     * @param array $rows
     * @return array
     */
    private function hydrateLayoutRowContainer(array $rows): array
    {
        foreach ($rows as $row) {
            $sql = "SELECT * FROM `LayoutRowCard` WHERE row_id = ?";
            $args = [$row->getId()];
            $sth = $this->database->prepare($sql);
            $sth->execute($args);
            $cards = $sth->fetchAll(PDO::FETCH_CLASS, LayoutRowCard::class);
            $row->setCards($cards);
        }

        return $rows;
    }

    /**
     * @param Program $program
     * @param array $data
     * @return bool
     */
    public function saveProgramAutoRedemption(Program $program, array $data): bool
    {
        if (!empty($data['auto_redemption'])) {
            // Purge one time autoredemptions to save only the those sent in request
            try {
                $sql = "DELETE FROM `autoredemption` WHERE program_id = ?";
                $sth = $this->database->prepare($sql);
                $sth->execute([$program->getId()]);
            } catch (\PDOException $e) {
                $this->setErrors(['could not purge autoredemptions.']);
                return false;
            }
            $autoRedemption = new AutoRedemption;
            $autoRedemption->exchange($data['auto_redemption']);
            $autoRedemption->setAllParticipant(1);
            $autoRedemption->setProgramId($program->getId());
            $this->placeSettings($autoRedemption);
        }
        if (!empty($data['one_time_auto_redemptions'])) {
            // Purge one time autoredemptions to save only the those sent in request
            try {
                $sql = "DELETE FROM `onetimeautoredemption` WHERE program_id = ?";
                $sth = $this->database->prepare($sql);
                $sth->execute([$program->getUniqueId()]);
            } catch (\PDOException $e) {
                $this->setErrors(['could not purge autoredemptions.']);
                return false;
            }

            foreach ($data['one_time_auto_redemptions'] as $autoRedemption) {
                $oneTimeAutoRedemption = new OneTimeAutoRedemption($autoRedemption);
                $oneTimeAutoRedemption->setProgramId($program->getUniqueId());

                $this->table = 'OneTimeAutoRedemption';
                $this->place($oneTimeAutoRedemption);
            }
        }
        return true;
    }

    /**
     * @param Program $program
     * @param array $data
     * @return bool
     * @throws \Exception
     */
    public function saveProgramOfflineRedemption(Program $program, array $data): bool
    {
        if (!empty($data)) {
            try {
                $sql = "DELETE FROM `OfflineRedemption` WHERE program_id = ?";
                $sth = $this->database->prepare($sql);
                $sth->execute([$program->getId()]);
            } catch (\PDOException $e) {
                throw new \Exception('could not purge offline redemptions.');
            }

            $offlineRedemption = new OfflineRedemption;
            $offlineRedemption->setActive($data['active']);
            $offlineRedemption->setSkus(json_encode($data['skus']));
            $offlineRedemption->setProgramId($program->getId());
            $this->table = 'OfflineRedemption';
            if ($saved = $this->place($offlineRedemption) === true) {
                return $saved;
            }
        }

        throw new \Exception('failed to save offline redemption.');
    }

    /**
     * @param LayoutRow[] $layoutRows
     * @return array
     */
    public function getLayoutRowsToArray(array $layoutRows): array
    {
        $container = [];
        /** @var LayoutRow[] $layoutRow */
        foreach ($layoutRows as $layoutRow) {
            $cardContainer = [];
            $row = $layoutRow->toArray();
            unset($row['priority']);
            unset($row['program_id']);
            unset($row['id']);
            unset($row['created_at']);
            unset($row['updated_at']);
            $container[$layoutRow->getPriority()] = $row;

            if (empty($layoutRow->getCards()) === false) {
                foreach ($layoutRow->getCards() as $card) {
                    $cardRow = $card->toArray();
                    unset($cardRow['id']);
                    unset($cardRow['row_id']);
                    unset($cardRow['created_at']);
                    unset($cardRow['updated_at']);
                    $cardRow['product_row'] = $cardRow['product_row'] !== null
                        ? json_decode($cardRow['product_row'])
                        : null;
                    $cardContainer[$cardRow['priority']] = $cardRow;
                }
                $container[$layoutRow->getPriority()]['card'] = $cardContainer;
            }
        }

        return $container;
    }

    public function saveProgramLayout(Program $program, array $layoutRows): bool
    {
        if (!empty($layoutRows)) {
            foreach ($layoutRows as $priority => $rowData) {
                $row = new LayoutRow;
                $label = $rowData['label'] ?? '';
                $row->setProgramId($program->getUniqueId());
                $row->setPriority($priority);
                $row->setLabel($label);
                unset($rowData['label']);
                $this->table = 'LayoutRow';
                $this->place($row);
                $this->table = 'Program';
                $row->setId($this->database->lastInsertId());
                $cards = $rowData['card'] ?? [];
                $this->saveRowCards($row, $cards);
            }
        }
        return true;
    }

    private function saveRowCards(LayoutRow $row, array $cards)
    {
        if ($this->isClone() === false) {
            try {
                // Purge row cards to save only the cards sent in request
                $sql = "DELETE FROM `LayoutRowCard` WHERE row_id = ?";
                $sth = $this->database->prepare($sql);
                $sth->execute([$row->getId()]);
            } catch (\PDOException $e) {
                throw new \Exception('could not purge row cards.');
            }
        }

        foreach ($cards as $cardPriority => $card) {
            $entity = new LayoutRowCard;
            if (!empty($card['image'])) {
                $imagePath = $row->getProgramId() . $row->getPriority() . $cardPriority;
                $image = $this->saveProgramLayoutImage($imagePath, $card['image']);
                $entity->setImage($image);
            }
            $productRow = null;
            if (!empty($card['product_row'])) {
                $productRow = json_encode($card['product_row']);
            }

            $textMarkdown = $card['text_markdown'] ?? null;

            $entity->setRowId($row->getId());
            $entity->setPriority($cardPriority);
            $entity->setType($card['type'] ?? 'image');
            $entity->setSize($card['size']);
            $entity->setProduct($card['product'] ?? null);
            $entity->setProductRow($productRow);
            $entity->setTextMarkdown($textMarkdown);
            $entity->setCardShow($card['card_show']);
            $entity->setTitle($card['title']);
            $entity->setLink($card['link'] === null || trim($card['link']) === '' ? null : $card['link']);

            $this->table = 'LayoutRowCard';
            $this->place($entity);
            $this->table = 'Program';
        }
    }

    public function deleteLayoutRow($rowId)
    {
        $sql = "DELETE FROM `LayoutRow` WHERE id = ?";
        $sth = $this->database->prepare($sql);
        return $sth->execute([$rowId]);
    }

    public function updatePublishColumn($program, $publish)
    {
        $sql = "UPDATE Program SET published = ? WHERE unique_id = ?";
        $sth = $this->database->prepare($sql);
        return $sth->execute([$publish, $program]);
    }

    public function cancelProgram($program)
    {
        $sql = "UPDATE Program SET published = 0, active = 0 WHERE unique_id = ?";
        $sth = $this->database->prepare($sql);
        return $sth->execute([$program]);
    }

    /**
     * @param $cardName
     * @param $imageData
     * @return null|string
     * @throws \Exception
     */
    private function saveProgramLayoutImage($cardName, $imageData): ?string
    {
        if ($this->isClone() === true) {
            $imageData = $this->getBase64EncodedExistingFile($imageData);
        }

        if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $type)) {
            $imageData = substr($imageData, strpos($imageData, ',') + 1);
            $type = strtolower($type[1]); // jpg, png, gif

            if (!in_array($type, ['jpg', 'jpeg', 'gif', 'png'])) {
                throw new \Exception('invalid image type:' . $type);
            }

            $imageData = base64_decode($imageData);

            if ($imageData === false) {
                throw new \Exception('base64_decode failed');
            }
        } elseif (substr($imageData, 0, 4) === 'http') {
            $imageData = @file_get_contents($imageData);
            if (empty($imageData) === true) {
                throw new \Exception('Image is not valid');
            }
        } elseif (in_array($this->getImageType($imageData), ['jpg', 'jpeg', 'gif', 'png'])) {
            //GUI sends the original file path on update
            $imageData = $this->getBase64EncodedExistingFile($imageData);
            if (empty($imageData) === true) {
                throw new \Exception('Image is not valid');
            }
        } else {
            throw new \Exception('did not match data URI with image data');
        }

        $imagePath = sha1($cardName) . "." . $type;
        $this->getFilesystem()
            ->put($imagePath, $imageData);

        return $imagePath;
    }

    private function getImageType(string $fileName)
    {
        $imageFile = explode('.', $fileName);
        return $imageFile[1];
    }

    /**
     * @param $program
     * @param $fileName
     * @return string
     * @throws \Exception
     */
    private function getBase64EncodedExistingFile($fileName)
    {
        $type = $this->getImageType($fileName);

        if (getenv('FILESYSTEM') === 'local') {
            $contents = file_get_contents(__DIR__ . '/../../public/resources/app/layout/'. $fileName);
        } else {
            $bucketName = getenv('GOOGLE_CDN_BUCKET');
            $cdnPath = "https://storage.googleapis.com/$bucketName/layout";
            $fileName = $cdnPath . '/' . $fileName;
            if (file_put_contents('/tmp/product_file.' . $type, file_get_contents($fileName))) {
                $contents = file_get_contents('/tmp/product_file.' . $type);
                unlink('/tmp/product_file.' . $type);
            } else {
                throw new \Exception('CDN File Download failure for ' . $fileName);
            }
        }

        return 'data:image/' . $type . ';base64,' . base64_encode($contents);
    }

    public function getProgramSweepstake(Program $program)
    {
        $sql = "SELECT * FROM `Sweepstake` WHERE program_id = ?";
        $args = [$program->getUniqueId()];
        if (!$sweepstake = $this->query($sql, $args, Sweepstake::class)) {
            return null;
        }

        return $this->hydrateSweepstake($sweepstake);
    }

    private function hydrateSweepstake(Sweepstake $sweepstake)
    {
        $sql = "SELECT * FROM `SweepstakeDraw` WHERE sweepstake_id = ? ORDER BY date ASC";
        $args = [$sweepstake->getId()];

        $sth = $this->database->prepare($sql);
        $sth->execute($args);
        $drawings = $sth->fetchAll(PDO::FETCH_CLASS, SweepstakeDraw::class);
        if ($drawings) {
            $sweepstake->setDrawing($drawings);
        }

        return $sweepstake;
    }

    public function saveSweepstake(Program $program, $sweepstakeData): bool
    {
        if ($this->placeSweepstake($program, $sweepstakeData)) {
            if ($program->getSweepstake()->isActive()) {
                $this->placeSweepstakeDrawing($program, $sweepstakeData);
            }

            return true;
        }

        return false;
    }

    public function placeSweepstake(Program $program, array $sweepstakeData): bool
    {
        $now = new \DateTime;
        $sweepstake = new Sweepstake;
        if ((int)$sweepstakeData['active'] === 1) {
            $sweepstake = $program->getSweepstake();
            $start = new \DateTime($sweepstakeData['start_date']);
            $end = new \DateTime($sweepstakeData['end_date']);

            $sweepstake->setStartDate($start->format('Y-m-d'));
            $sweepstake->setEndDate($end->format('Y-m-d'));
            $sweepstake->setPoint($sweepstakeData['point']);
            $sweepstake->setActive(1);
        }
        $sweepstake->setProgramId($program->getUniqueId());

        if ($sweepstake->getId() === null) {
            $sweepstake->setCreatedAt($now);
        }

        $sweepstake->setUpdatedAt($now);

        $this->table = 'Sweepstake';
        $result = $this->place($sweepstake);
        $sweepstake->setId($this->database->lastInsertId());
        $this->table = 'Program';
        $program->setSweepstake($sweepstake);
        return $result;
    }

    public function placeSweepstakeDrawing(Program $program, array $sweepstakeData): bool
    {
        $success = true;
        //@TODO why not just have the entity return it's name for table ? They are always the same.
        $this->table = 'SweepstakeDraw';
        if (!empty($sweepstakeData['draw_date'])) {
            foreach ($sweepstakeData['draw_date'] as $key => $date) {
                $date = new \DateTime($date);
                $draw = new SweepstakeDraw;
                $draw->setDate($date->format('Y-m-d'));
                $draw->setDrawCount($sweepstakeData['draw_count'][$key]);
                $draw->setSweepstakeId($program->getSweepstake()->getId());
                if ($this->place($draw) === false) {
                    $success = false;
                    break;
                }
            }
        }
        $this->table = 'Program';
        return $success;
    }

    public function getParticiantTotal($id)
    {
        $sql = <<<SQL
SELECT COUNT(id) AS program_participants FROM participant WHERE active = 1 AND program_id = '{$id}';
SQL;

        $sth = $this->getDatabase()->prepare($sql);
        $sth->execute();
        $total = $sth->fetch();
        return $total['program_participants'];
    }

    public function getTransactionTotal($id)
    {
        $sql = <<<SQL
SELECT count(t.total) AS program_transactions 
FROM transaction t
LEFT JOIN participant p
ON t.participant_id = p.id
WHERE p.program_id = '{$id}';
SQL;

        $sth = $this->getDatabase()->prepare($sql);
        $sth->execute();
        $total = $sth->fetch();
        return $total['program_transactions'];
    }
}
