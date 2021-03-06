<?php
namespace Controllers\Program;

use Controllers\AbstractOutputNormalizer;
use Entities\Faqs;
use Entities\FeaturedProduct;
use Entities\LayoutRow;
use Entities\Program;

class OutputNormalizer extends AbstractOutputNormalizer
{
    public function get(): array
    {
        /** @var Program $program */
        $program = parent::get();
        $return = $this->scrub($program->toArray(), [
            'id',
            'domain_id',
            'phone',
            'address1',
            'address2',
            'city',
            'state',
            'zip',
            'role',
            'organization_id',
            'contact_reference',
            'accounting_contact_reference',
            'invoice_to'
        ]);

        if ($program->getDomain() !== null) {
            $return['url'] = $return['url'] . '.' . $program->getDomain()->getUrl();
        }

        $return['organization'] = $program->getOrganization()->getUniqueId();
        $return['contact'] = $program->getContact();
        $return['accounting_contact'] = $program->getAccountingContact();
        $categories = $program->getProductCriteria()->getCategories();
        $products = $program->getProductCriteria()->getProducts();
        $brands = $program->getProductCriteria()->getBrands();
        $groups = $program->getProductCriteria()->getGroups();
        $excludeProducts = $program->getProductCriteria()->getExcludeProducts();
        $excludeBrands = $program->getProductCriteria()->getExcludeBrands();
        $excludeVendors = $program->getProductCriteria()->getExcludeVendors();
        $featuredPageTitle = $program->getProductCriteria()->getFeaturedPageTitle();
        $return['productCriteria'] = [
            'price' => [
                'min' => $program->getProductCriteria()->getMinFilter(),
                'max' => $program->getProductCriteria()->getMaxFilter()
            ],
            'categories' => $categories,
            'products' => $products,
            'brands' => $brands,
            'groups' => $groups,
            'exclude_products' => $excludeProducts,
            'exclude_brands' => $excludeBrands,
            'exclude_vendors' => $excludeVendors,
            'featured_page_title' => $featuredPageTitle
        ];

        $return['featured_products'] = [];

        if (!empty($program->getFeaturedProducts())) {
            foreach ($program->getFeaturedProducts() as $product) {
                /** @var FeaturedProduct $product */
                $return['featured_products'][] = $product->getSku();
            }
        }
        $return['auto_redemption'] = $program->getAutoRedemption();
        $return['one_time_auto_redemptions'] = $program->getOneTimeAutoRedemptions();
        $return['programTypes'] = $this->scrubList($program->getProgramTypes(), ['actions', 'created_at', 'updated_at']);
        $return['actions'] = $program->getActions();
        return $return;
    }

    public function getList(): array
    {
        $list = parent::get();

        $return = $this->scrubList($list, [
            'id',
            'address1',
            'address2',
            'city',
            'state',
            'zip',
            'role',
            'organization_id',
            'domain_id',
            'url'
        ]);

        foreach ($return as $key => $program) {
            $return[$key]['organization'] = $program['organization_reference'];
            unset($return[$key]['organization_reference']);
        }

        return $return;
    }

    public function getLayout(): array
    {
        /** @var LayoutRow[] $rows */
        $rows = parent::get();
        $cardContainer = [];
        foreach ($rows as $row) {
            $cardContainer[] = [
                'label' => $row->getLabel(),
                'priority' => $row->getPriority(),
                'cards' => $this->scrubList($row->getCards(), [
                    'id',
                    'created_at',
                    'updated_at'
                ])
            ];
        }
        return $cardContainer;
    }

    public function getFaqs(): array
    {
        /** @var Faqs[] $faqs */
        $faqs = parent::get();
        $faqsContainer = [];
        foreach ($faqs as $faq) {
            $faqsContainer[] = [
                'question' => $faq->getQuestion(),
                'answer' => $faq->getAnswer()
            ];
        }
        return $faqsContainer;
    }
}
