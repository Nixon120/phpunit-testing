<?php

namespace Controllers\Organization;

use Controllers\AbstractOutputNormalizer;
use Entities\Domain;
use Entities\Organization;

class OutputNormalizer extends AbstractOutputNormalizer
{
    public function get(): array
    {
        /** @var Organization $organization */
        $organization = parent::get();
        $parent = null;
        if ($organization->getParent() !== null) {
            $parent = $organization->getParent()->getUniqueId();
        }
        $domains = [];
        foreach ($organization->getDomains() as $key => $domain) {
            /** @var Domain $domain */
            if ($domain->getOrganizationId() === $organization->getId()) {
                array_push($domains, $domain->getUrl());
            }
        }
        $return = $this->scrub($organization->toArray(), [
            'id',
            'lvl',
            'rgt',
            'lft',
            'parent_id',
            'username',
            'company_contact_reference',
            'accounts_payable_contact_reference'
        ]);
        $return['parent'] = $parent;
        $return['domains'] = $domains;
        $return['company_contact'] = $organization->getCompanyContact();
        $return['accounts_payable_contact'] = $organization->getAccountsPayableContact();
        return $return;
    }

    public function getList(): array
    {
        $list = $this->scrubList(parent::get(), [
            'id',
            'lvl',
            'rgt',
            'lft'
        ]);

        foreach (parent::get() as $key => $entity) {
            if (get_class($entity) == 'Entities\Organization') {
                $list[$key]['program_count'] = $entity->getProgramCount();
            }
        }

        foreach ($list as $key => $organization) {
            $list[$key]['parent'] = $organization['parent_id'];
            unset($list[$key]['parent_id']);
        }
        return $list;
    }
}
