<?php

namespace AllDigitalRewards\RewardStack\Services\Participant\StatusEnum;

abstract class StatusEnum extends BasicEnum
{
    const ACTIVE = 1;
    const HOLD = 2;
    const INACTIVE = 3;
    const CANCELLED = 4;
    const DATADEL = 5;
}
