<?php

use \League\Flysystem\Filesystem;

class ProgramRepositoryTest extends \PHPUnit\Framework\TestCase
{
    public $mockDatabase;

    public $mockReportEntity;

    public $mockFilesystem;

    public $client;

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\PDO
     */
    private function getMockDatabase()
    {
        if (!$this->mockDatabase) {
            $this->mockDatabase = $this
                ->getMockBuilder(\PDO::class)
                ->disableOriginalConstructor()
                ->setMethods([])
                ->getMock();
        }

        return $this->mockDatabase;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|Filesystem
     */
    private function getMockFileSystem()
    {
        if (!$this->mockFilesystem) {
            $this->mockFilesystem = $this
                ->getMockBuilder(Filesystem::class)
                ->disableOriginalConstructor()
                ->setMethods([])
                ->getMock();
        }

        return $this->mockFilesystem;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\AllDigitalRewards\Services\Catalog\Client
     */
    private function getMockClient()
    {
        if (!$this->client) {
            $this->client = $this
                ->getMockBuilder(\AllDigitalRewards\Services\Catalog\Client::class)
                ->disableOriginalConstructor()
                ->setMethods([])
                ->getMock();
        }

        return $this->client;
    }

    /**
     * @return \Repositories\ProgramRepository
     */
    private function getProgramRepository()
    {
        $repository = new \Repositories\ProgramRepository(
            $this->getMockDatabase(),
            $this->getMockClient(),
            $this->getMockFileSystem()
        );

        return $repository;
    }

    public function testValidateProgramWithHyphen(){

        $program = new \Entities\Program($this->getProgramFixture());
        $program->setUniqueId('TESTHYPHEN');

        $this->assertTrue($this->getProgramRepository()->validate($program));
    }

    public function testValidateProgramWithUnderscore(){

        $program = new \Entities\Program($this->getProgramFixture());
        $program->setUniqueId('TEST_UNDERSCORE');

        $this->assertTrue($this->getProgramRepository()->validate($program));
    }

    public function testValidateProgramWithSpace(){

        $program = new \Entities\Program($this->getProgramFixture());
        $program->setUniqueId('TEST SPACE');

        $this->assertFalse($this->getProgramRepository()->validate($program));
    }

    private function getProgramFixture()
    {
        return [
            "name" => "test",
            "point" => 10,
            "unique_id" => "TEST123",
            "cost_center_id" => "122",
            "organization_id" => 12,
            "contact" => null,
            "deposit_amount" => 12,
            "url" => "test",
            "domain" => null,
            "auto_redemption" => null
        ];
    }

}
