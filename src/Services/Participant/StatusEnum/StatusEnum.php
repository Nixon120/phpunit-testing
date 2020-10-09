<?php

namespace AllDigitalRewards\RewardStack\Services\Participant\StatusEnum;

abstract class StatusEnum extends BasicEnum
{
    const ACTIVE = 1;
    const HOLD = 2;
    const CANCELLED = 3;
    const DATADEL = 4;
}
