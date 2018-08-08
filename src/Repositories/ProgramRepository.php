<?php

namespace Repositories;

use AllDigitalRewards\Services\Catalog\Client;
use Entities\AutoRedemption;
use Entities\Contact;
use Entities\Domain;
use Entities\FeaturedProduct;
use Entities\LayoutRow;
use Entities\LayoutRowCard;
use Entities\Organization;
use Entities\ProductCriteria;
use Entities\Program;
use Entities\Sweepstake;
use Entities\SweepstakeDraw;
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
Program.domain_id, Program.meta, Program.logo, Program.active, Program.updated_at, Program.created_at, Program.published, 
Organization.unique_id AS organization_reference 
FROM Program 
JOIN Organization ON Program.organization_id = Organization.id
LEFT JOIN Domain ON Domain.id = Program.domain_id 
{$where}
SQL;

        return $sql;
    }

    //@TODO change this to getByUnique or getById
    public function getProgram($id, $uniqueLookup = true):?Program
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

    private function hydrateProgram(Program $program)
    {
        $program->setOrganization($this->getProgramOrganization($program->getOrganizationId()));
        $domain = $this->getProgramDomain($program->getDomainId());
        $program->setDomain($domain);
        $program->setAutoRedemption($this->getAutoRedemption($program));
        $program->setContact($this->getContact($program));
        $program->setAccountingContact($this->getAccountingContact($program));
        $program->setProductCriteria($this->getProductCriteria($program));
        $program->setLayoutRows($this->getProgramLayout($program));
        $program->setSweepstake($this->getProgramSweepstake($program));
        $program->setFeaturedProducts($this->getProgramFeaturedProducts($program));
        return $program;
    }

    private function getProgramFeaturedProducts(Program $program)
    {
        $sql = "SELECT * FROM `FeaturedProduct` WHERE program_id = ?";
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

    public function getProgramByDomain(string $domain):?Program
    {
        $domain = $this->splitDomain($domain);
        $sql = "SELECT * FROM Program WHERE url = ?";
        $args = [$domain->url];

        if (!$program = $this->query($sql, $args, Program::class)) {
            return null;
        }

        return $this->hydrateProgram($program);
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

    public function getProgramDomain(?string $id):?Domain
    {
        $sql = "SELECT * FROM `Domain` WHERE id = ?";
        $args = [$id];
        return $this->query($sql, $args, Domain::class);
    }

    public function getProgramDomainByDomainName(string $domain):?Domain
    {
        $sql = "SELECT * FROM `Domain` WHERE url = ?";
        $args = [$domain];
        return $this->query($sql, $args, Domain::class);
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

    public function placeSettings(AutoRedemption $settings): bool
    {
        $this->table = 'AutoRedemption';
        $result = $this->place($settings);
        $this->table = 'Program';

        return $result;
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

    private function getProducts($products)
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

    public function getProductCriteria(Program $program):?ProductCriteria
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
        $criteria->setCategories($this->getCategories($criteria->getCategoryFilter()));
        $criteria->setBrands($this->getBrands($criteria->getBrandFilter()));
        $criteria->setProducts($this->getProducts($criteria->getProductFilter()));
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
            ->attribute('logo', Validator::oneOf(
                Validator::stringType()->setName('Logo'),
                Validator::nullType()
            ))
            ->attribute('point', Validator::numeric()->min(1)->setName('Point'))
            ->attribute('unique_id', Validator::notEmpty()->alnum('_ -')->noWhitespace()->setName('Unique Id'))
            ->attribute('organization_id', Validator::notEmpty()->setName('Organization'))
            ->attribute('deposit_amount', Validator::optional(Validator::numeric()->setName('Deposit')))
            ->attribute('low_level_deposit', Validator::optional(Validator::numeric()->setName('LowLevelDeposit')))
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

    public function saveFeaturedProducts(Program $program, array $productSkuContainer): bool
    {
        $this->table = 'FeaturedProduct';
        $this->deleteProgramFeaturedProductsWhereNotIn($program, $productSkuContainer);
        foreach ($productSkuContainer as $sku) {
            $product = new FeaturedProduct;
            $product->setProgramId($program->getUniqueId());
            $product->setSku($sku);
            $this->place($product);
        }
        $this->table = 'Program';

        return true;
    }

    private function deleteProgramFeaturedProductsWhereNotIn(Program $program, array $skuContainer)
    {
        if (!empty($skuContainer)) {
            $placeholder = rtrim(str_repeat('?, ', count($skuContainer)), ', ');
            $sql = <<<SQL
DELETE FROM FeaturedProduct WHERE FeaturedProduct.sku NOT IN ({$placeholder}) AND FeaturedProduct.program_id = ?
SQL;
            $skuContainer[] = $program->getUniqueId();
            $sth = $this->getDatabase()->prepare($sql);
            return $sth->execute($skuContainer);
        }

        return true;
    }

    public function saveProductCriteria(Program $program, $filterData): bool
    {
        $criteria = new ProductCriteria;
        $criteria->setFilter($filterData);
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
            $textMarkdown = null;
            if (!empty($card['text_markdown'])) {
                $textMarkdown = json_encode($card['text_markdown']);
            }

            $entity->setRowId($row->getId());
            $entity->setPriority($cardPriority);
            $entity->setType($card['type'] ?? 'image');
            $entity->setSize($card['size']);
            $entity->setProduct($card['product'] ?? null);
            $entity->setProductRow($productRow);
            $entity->setTextMarkdown($textMarkdown);
            $entity->setIsloggedin($card['isloggedin']);
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

    /**
     * @param $cardName
     * @param $imageData
     * @return null|string
     * @throws \Exception
     */
    private function saveProgramLayoutImage($cardName, $imageData): ?string
    {
        if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $type)) {
            $imageData = substr($imageData, strpos($imageData, ',') + 1);
            $type = strtolower($type[1]); // jpg, png, gif

            if (!in_array($type, [ 'jpg', 'jpeg', 'gif', 'png' ])) {
                throw new \Exception('invalid image type');
            }

            $imageData = base64_decode($imageData);

            if ($imageData === false) {
                throw new \Exception('base64_decode failed');
            }
        } else {
            throw new \Exception('did not match data URI with image data');
        }

        $imagePath = sha1($cardName) . "." . $type;
        $this->getFilesystem()
            ->put($imagePath, $imageData);

        return $imagePath;
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
}
