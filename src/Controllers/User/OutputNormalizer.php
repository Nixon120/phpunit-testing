<?php
namespace Controllers\User;

use Controllers\AbstractOutputNormalizer;
use Entities\User;

class OutputNormalizer extends AbstractOutputNormalizer
{
    public function get(): array
    {
        /** @var User $user */
        $user = parent::get();
        $return = $user->toArray();
        if ($user->getOrganization() !== null) {
            $return['organization'] = $user->getOrganization()->getUniqueId();
        }
        $return = $this->scrub($return, [
            'password',
            'organization_id',
        ]);
        return $return;
    }


    public function getList(): array
    {
        $list = parent::get();

        $return = $this->scrubList($list, [
            'sso',
            'organization_id',
        ]);

        foreach ($return as $key => $user) {
            $return[$key]['program'] = $user['program_reference'];
            $return[$key]['organization'] = $user['organization_reference'];
            unset($return[$key]['organization_reference'], $return[$key]['program_reference']);
        }

        return $return;
    }
}
