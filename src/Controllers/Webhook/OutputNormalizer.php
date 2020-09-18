<?php

namespace Controllers\Webhook;

use Controllers\AbstractOutputNormalizer;
use Entities\Webhook;

class OutputNormalizer extends AbstractOutputNormalizer
{
    public function get(): array
    {
        /** @var Webhook $webhook */
        $webhook = parent::get();

        $return = $this->scrub(
            $webhook->toArray(),
            [
                'username',
                'password',
                'organization_id',
            ]
        );

        return $return;
    }


    public function getList(): array
    {
        $list = parent::get();

        return $this->scrubList(
            $list,
            [
                'username',
                'password',
                'organization_id',
            ]
        );
    }
}
