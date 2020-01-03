<?php


namespace AllDigitalRewards\RewardStack\Traits;

trait MetaValidationTrait
{
    /**
     * @param $metaCollection
     * @return bool
     */
    public function hasWellFormedMeta($metaCollection): bool
    {
        if ($metaCollection === null) {
            return true;
        }

        foreach ($metaCollection as $meta) {
            if (is_array($meta) === false) {
                return false;
            }

            foreach ($meta as $key => $value) {
                if (empty($key) === true || empty($value) === true) {
                    // Not valid meta;
                    return false;
                }
            }
        }

        return true;
    }
}
