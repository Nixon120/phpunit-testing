<?php
namespace Entities;

use AllDigitalRewards\Services\Catalog\Entity\Product;
use Entities\Traits\ProgramTrait;
use Entities\Traits\StatusTrait;
use Entities\Traits\TimestampTrait;
use Services\Scheduler\Tasks\ScheduledRedemption;

class AutoRedemption extends Base
{
    use TimestampTrait;
    use StatusTrait;
    use ProgramTrait;

    private $eligibleTimeReferences = [
        'minute' => '* * * * *',
        'everySecondMinute' => '*/2 * * * *',
        'hourly' => '0 * * * *',
        'daily' => '0 2 * * *', // every day, 2 am
        'weekly' => '0 2 * * 0', // every week, sunday 2 am
        'biweekly' => '0 2 1,2,3,4,5,6,7,15,16,17,18,19,20,21 * 0', // first and third sunday of month, 2am
        'monthly' => '0 2 1 * *', // first of month, 2am
        'yearly' => '0 2 1 1 *' // first of year, 2 am
    ];

    public $product_sku;

    private $product;

    public $interval;

    public $schedule;

    public $all_participant;

    public function __construct(array $data = null)
    {
        parent::__construct();

        if (!is_null($data)) {
            $this->exchange($data);
        }
    }

    public function isEligibleTime($time):bool
    {
        return array_key_exists($time, $this->eligibleTimeReferences);
    }

    public function getProductSku()
    {
        return $this->product_sku;
    }

    public function setProductSku(string $sku)
    {
        $this->product_sku = $sku;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(Product $product)
    {
        $this->product = $product;
    }

    /**
     * @return mixed
     */
    public function getInterval()
    {
        switch ($this->interval) {
            case 1:
                return 'scheduled';
                break;
            case 2:
                return 'instant';
                break;
        }
    }

    /**
     * @param mixed $interval
     */
    public function setInterval($interval)
    {
        $value = 2; // Instant

        if (in_array($interval, ['scheduled', 1], true)) {
            $value = 1;
        }

        $this->interval = $value;
    }

    /**
     * @return mixed
     */
    public function getSchedule()
    {
        return $this->schedule;
    }

    /**
     * @param mixed $schedule
     */
    public function setSchedule($schedule)
    {
        $this->schedule = $schedule;
    }

    public function getCronExpression()
    {
        return $this->eligibleTimeReferences[$this->schedule];
    }

    /**
     * @return mixed
     */
    public function getAllParticipant()
    {
        return $this->all_participant;
    }

    /**
     * @param mixed $all_participant
     */
    public function setAllParticipant($all_participant)
    {
        $this->all_participant = $all_participant;
    }

    public function getTask(): ScheduledRedemption
    {
        return new ScheduledRedemption();
    }
}
