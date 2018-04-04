<?php

namespace Services\Scheduler\Tasks;

use AllDigitalRewards\Services\Catalog\Entity\Product;
use Entities\AutoRedemption;
use Entities\Participant;
use Entities\Program;
use pmill\Scheduler\Tasks\Task as ScheduledTask;
use Psr\Container\ContainerInterface;
use Services\Participant\Transaction;
use Services\Product\ProductRead;
use Traits\LoggerAwareTrait;

class ScheduledRedemption extends ScheduledTask
{
    use LoggerAwareTrait;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var AutoRedemption
     */
    private $autoRedemption;

    /**
     * @var ProductRead
     */
    private $productRead;

    /**
     * @var \Services\Program\Program
     */
    private $programService;

    /**
     * @var Transaction
     */
    private $transactionService;

    /**
     * @var \Services\Participant\Participant
     */
    private $participantService;

    /**
     * @var Product
     */
    private $product;

    /**
     * @var Program
     */
    private $program;

    /**
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    public function setAutoRedemption(AutoRedemption $autoRedemption)
    {
        $this->autoRedemption = $autoRedemption;
    }

    public function getAutoRedemption(): AutoRedemption
    {
        return $this->autoRedemption;
    }

    public function setProduct(Product $product)
    {
        $this->product = $product;
    }

    public function getProduct(): Product
    {
        if ($this->product === null) {
            $this->product = $this
                ->getProductReadService()
                ->getById($this->getAutoRedemption()->getProductSku());
        }

        return $this->product;
    }

    public function setProgram(Program $program)
    {
        $this->program = $program;
    }

    public function getProgram(): Program
    {
        if ($this->program === null) {
            $this->program = $this->getProgramService()
                ->getSingle($this->getAutoRedemption()->getProgramId(), false);
        }

        return $this->program;
    }

    public function setTransactionService(Transaction $transaction)
    {
        $this->transactionService = $transaction;
    }

    public function getTransactionService(): Transaction
    {
        if ($this->transactionService === null) {
            $this->transactionService = $this->container
                ->get('participant')
                ->getTransactionService();
        }

        return $this->transactionService;
    }

    public function setParticipantService(\Services\Participant\Participant $participant)
    {
        $this->participantService = $participant;
    }

    public function getParticipantService()
    {
        if ($this->participantService === null) {
            $this->participantService = $this->container
                ->get('participant')
                ->getService();
        }

        return $this->participantService;
    }

    public function setProductReadService(ProductRead $productRead)
    {
        $this->productRead = $productRead;
    }

    public function getProductReadService(): ProductRead
    {
        if ($this->productRead === null) {
            $this->productRead = $this->container
                ->get('product')->getProductRead();
        }

        return $this->productRead;
    }

    public function setProgramService(\Services\Program\Program $program)
    {
        $this->programService = $program;
    }

    public function getProgramService(): \Services\Program\Program
    {
        if ($this->programService === null) {
            $this->programService = $this->container
                ->get('program')->getService();
        }

        return $this->programService;
    }

    public function run()
    {
        if ($this->getProduct()->isPriceRanged()) {
            return $this->runRangedPricingAutoRedemption();
        }

        return $this->runAutoRedemption();
    }

    private function noParticipantsEligible()
    {
        $this->getLogger()->info('No participants are eligible for auto-redeem');
        $this->setOutput('No participants have enough points to auto-redeem.');
        return;
    }

    private function runAutoRedemption()
    {
        $participants = $this->getEligibleStaticPricingParticipant();

        if (empty($participants)) {
            return $this->noParticipantsEligible();
        }

        $this->iterateEligibleParticipant($participants);

        $completedOutput = 'Static product scheduled redemption completed';
        $this->setOutput($completedOutput);
        $this->getLogger()->info($completedOutput, [
            'program' => $this->getProgram()->getUniqueId(),
            'participants' => count($participants)
        ]);
    }

    private function runRangedPricingAutoRedemption()
    {
        $participants = $this->getEligibleRangedPricingParticipants();

        if (empty($participants)) {
            return $this->noParticipantsEligible();
        }

        $this->iterateEligibleParticipant($participants);
        $completedOutput = 'Ranged product scheduled redemption completed';
        $this->setOutput($completedOutput);
        $this->getLogger()->info($completedOutput, [
            'program' => $this->getProgram()->getUniqueId(),
            'participants' => count($participants)
        ]);
    }

    private function getEligibleStaticPricingParticipant()
    {
        $minimumCost = bcmul(
            $this->getProduct()->getPriceTotal(),
            $this->getProgram()->getPoint(),
            2
        );
        // Fetch participants with enough points to purchase product
        return $this->getParticipantService()
            ->getProgramParticipantsWithPointsGreaterThan(
                $this->getProgram()->getUniqueId(),
                $minimumCost
            );
    }

    private function getEligibleRangedPricingParticipants()
    {
        $minimumCost = bcmul($this->getProduct()->getCalculatedMinimum(), $this->getProgram()->getPoint(), 2);
        // Fetch participants with enough points to purchase product
        return $this->getParticipantService()
            ->getProgramParticipantsWithPointsGreaterThan(
                $this->getProgram()->getUniqueId(),
                $minimumCost
            );
    }

    /**
     * @param Participant[] $participants
     */
    private function iterateEligibleParticipant(array $participants)
    {
        // Iterate Participants & Create Transaction for Product
        foreach ($participants as $participant) {
            $fullParticipant = $this
                ->getParticipantService()
                ->getSingle($participant->getUniqueId());

            $this->issueParticipantRedemption($fullParticipant);
        }
    }

    private function issueParticipantRedemption(Participant $participant)
    {
        $transactionService = $this->getTransactionService();
        // This is required for the Transaction service.
        $transaction = $transactionService->insertParticipantTransaction(
            $participant,
            $this->getRedemptionContainer($participant)
        );
    }

    private function getParticipantPointConsumptionValue(
        Participant $participant
    ) {
    
        if ($this->getProduct()->isPriceRanged()) {
            $pointAmountToConsume = $participant->getCredit();
            $maximumCost = bcmul($this->getProduct()->getPriceRangedMax(), $this->getProgram()->getPoint(), 2);

            if ($pointAmountToConsume > $maximumCost) {
                $pointAmountToConsume = $maximumCost;
            }

            $pointAmountToConsume = bcdiv($pointAmountToConsume, $this->getProgram()->getPoint(), 2);

            return $pointAmountToConsume;
        }

        return null;
    }

    private function getRedemptionContainer(Participant $participant): array
    {
        $return['products'] = [$this->getFormattedProduct($participant)];
        if ($participant->getAddress() !== null) {
            $return['shipping'] = [
                'firstname' => $participant->getFirstname(),
                'lastname' => $participant->getLastname(),
                'address1' => $participant->getAddress()->getAddress1(),
                'address2' => $participant->getAddress()->getAddress2(),
                'city' => $participant->getAddress()->getCity(),
                'state' => $participant->getAddress()->getState(),
                'zip' => $participant->getAddress()->getZip()
            ];
        }

        return $return;
    }

    private function getFormattedProduct(Participant $participant)
    {
        $pointAmountToConsume = $this->getParticipantPointConsumptionValue($participant);

        $products = [
            'sku' => $this->getProduct()->getSku(),
            'quantity' => 1
        ];

        if ($pointAmountToConsume !== null) {
            $products['amount'] = $pointAmountToConsume;
        }

        return $products;
    }
}
