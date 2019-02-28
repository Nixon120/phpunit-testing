<?php
namespace Entities;

use Entities\Traits\ProgramTrait;
use Entities\Traits\StatusTrait;
use Entities\Traits\TimestampTrait;

class OfflineRedemption extends Base
{
    use TimestampTrait;
    use StatusTrait;
    use ProgramTrait;

    public $skus;

    public function __construct(array $data = null)
    {
        parent::__construct();

        if (!is_null($data)) {
            $this->exchange($data);
        }
    }

    /**
     * @return mixed
     */
    public function getSkus()
    {
        return $this->skus;
    }

    /**
     * @param mixed $skus
     */
    public function setSkus($skus)
    {
        $this->skus = $skus;
    }
}
