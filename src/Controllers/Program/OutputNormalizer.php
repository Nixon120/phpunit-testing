<?php
namespace Controllers\Program;

use Controllers\AbstractOutputNormalizer;
use Entities\FeaturedProduct;
use Entities\LayoutRow;
use Entities\Program;
use Entities\ProgramLayout;

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
            'invoice_to',
            'issue_1099',
            'employee_payroll_file'
        ]);

        if ($program->getDomain() !== null) {
            $return['url'] = $return['url'] . '.' . $program->getDomain()->getUrl();
        }
        $return['organization'] = $program->getOrganization()->getUniqueId();
        $return['contact'] = $program->getContact();
        $categories = $program->getProductCriteria()->getCategories();
        $products = $program->getProductCriteria()->getProducts();
        $brands = $program->getProductCriteria()->getBrands();
        $return['productCriteria'] = [
            'price' => [
                'min' => $program->getProductCriteria()->getMinFilter(),
                'max' => $program->getProductCriteria()->getMaxFilter()
            ],
            'categories' => $categories,
            'products' => $products,
            'brands' => $brands
        ];

        $return['featured_products'] = [];

        if (!empty($program->getFeaturedProducts())) {
            foreach ($program->getFeaturedProducts() as $product) {
                /** @var FeaturedProduct $product */
                $return['featured_products'][] = $product->getSku();
            }
        }
        $return['sub_domain']=$program->getUrl();
        $return['domain']=$program->getDomain();
        $return['auto_redemption']=$program->getAutoRedemption();
        return $return;
    }

    public function getList(): array
    {
        $list = parent::get();

        $return = $this->scrubList($list, [
            'logo',
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
                'cards' => $this->scrubList($row->getCards(), [
                    'id',
                    'created_at',
                    'updated_at'
                ])
            ];
        }
        return $cardContainer;
    }
}
