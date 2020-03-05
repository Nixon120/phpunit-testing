<?php

namespace Services\Participant;

use AllDigitalRewards\RewardStack\Client;
use AllDigitalRewards\RewardStack\Common\AbstractCollectionApiResponse;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use \PDO as PDO;
use Slim\Container;

class TransactionRefundProcessor
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @var \Services\Participant\Transaction
     */
    private $transactionService;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @return Container
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * @param Container $container
     */
    public function setContainer(Container $container): void
    {
        $this->container = $container;
    }

    /**
     * @return mixed
     */
    public function getTransactionService(): \Services\Participant\Transaction
    {
        if($this->transactionService === null) {
            $this->transactionService = $this->getContainer()->get('participant')->getTransactionService();
        }

        return $this->transactionService;
    }

    public function getDatabase(): \PDO
    {
        return $this->getTransactionService()->getTransactionRepository()->getDatabase();
    }

    public function processPendingRefundRequests(): bool
    {
        $pendingCollection = $this->getPendingRefunds();
        foreach($pendingCollection as $refund) {
            if($this->issueRefund($refund) === false) {
                // Report incomplete refund issuance and continue
                die('yep');
            }
        }

        return true;
    }

    private function getRewardStackClient(): Client
    {
        $credentials = new \AllDigitalRewards\RewardStack\Auth\Credentials(
            'test@alldigitalrewards.com',
            'password'
        );

        $uri = new \GuzzleHttp\Psr7\Uri('http://localhost');

        $httpClient = new \GuzzleHttp\Client();

        $authProxy = new \AllDigitalRewards\RewardStack\Auth\AuthProxy($credentials, $uri, $httpClient);

        return new \AllDigitalRewards\RewardStack\Client($authProxy);
    }


    private function issueRefund(array $refund): bool
    {
        $success = $this->issueCreditAdjustment($refund);
        // send data to RA queue for dispatch to RA refund endpoint
        return $success;
    }

    private function issueCreditAdjustment(array $refund): bool
    {
        $createAdjustmentsRequest = new \AllDigitalRewards\RewardStack\Adjustment\CreateAdjustmentRequest(
            $refund['participant_unique_id'],
            'credit',
            bcmul($refund['total_refund_amount'], $refund['program_point_value'], 2),
            'Refund for GUID: ' . $refund['guid']
        );

        try {
            /**
             * @var AbstractCollectionApiResponse $createAdjustmentsResponse
             */
            $createAdjustmentsResponse = $this->getRewardStackClient()->request($createAdjustmentsRequest);
            return true;
        } catch(ClientException $e) {
            return false;
        } catch(ServerException $e) {
            return false;
        }
    }

    private function getPendingRefunds(): array
    {
        $sql = <<<SQL
SELECT 
    (SELECT program.point FROM program WHERE program.id = participant.program_id) as program_point_value,
    participant.unique_id as participant_unique_id, 
    ((transactionproduct.retail + transactionproduct.handling + transactionproduct.shipping) * transactionitem.quantity) as total_refund_amount,
    transactionitem.guid,
    transaction_item_refund.* 
FROM transaction_item_refund 
JOIN transactionitem ON transaction_item_refund.transaction_item_id = transactionitem.id
JOIN transactionproduct ON transactionitem.reference_id = transactionproduct.reference_id
JOIN `transaction` ON transaction_item_refund.transaction_id = `transaction`.id
JOIN participant ON `transaction`.participant_id = participant.id
WHERE complete = 0
SQL;

        $sth = $this->getDatabase()->query($sql);
        return $sth->fetchAll();
    }

}
