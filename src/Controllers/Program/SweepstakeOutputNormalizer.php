<?php
namespace Controllers\Program;

use Controllers\AbstractOutputNormalizer;
use Entities\LayoutRow;
use Entities\Sweepstake;

class SweepstakeOutputNormalizer extends AbstractOutputNormalizer
{
    public function get(): array
    {
        /** @var Sweepstake $sweepstake */
        $sweepstake = parent::get();
        $return = $this->scrub($sweepstake->toArray(), []);
        $return['drawings'] = $sweepstake->getDrawing();

        return $return;
    }

    public function getList(): array
    {
        $list = parent::get();

        $return = $this->scrubList($list, []);
        return $return;
    }
}
