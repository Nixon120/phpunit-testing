<?php

abstract class AbstractUserServiceTest extends \PHPUnit\Framework\TestCase
{
    private $container;

    private $userServiceFactory;

    private $reportServiceFactory;

    public $mockDatabase;

    private $mockUser;

    protected function getPdoStatementMock()
    {
        return $this->getMockBuilder(PDOStatement::class)
            ->disableOriginalConstructor()
            ->setMethods(["execute", "fetch", "fetchAll", "setFetchMode"])
            ->getMock();
    }

    protected function getMockDatabase()
    {
        if (!$this->mockDatabase) {
            $this->mockDatabase = $this
                ->getMockBuilder(\PDO::class)
                ->disableOriginalConstructor()
                ->setMethods(["beginTransaction", "commit", "prepare", "lastInsertId"])
                ->getMock();
        }

        return $this->mockDatabase;
    }

    protected function getMockSlimContainer()
    {
        if ($this->container === null) {
            $settings = require __DIR__ . '/../../../src/settings.php';
            $container = new \Slim\Container($settings);
            require __DIR__ . '/../../../src/dependencies.php';
            $this->container = $container;
        }

        return $this->container;
    }

    protected function getUserServiceFactory(): \Services\User\ServiceFactory
    {
        if ($this->userServiceFactory === null) {
            $this->userServiceFactory = new Services\User\ServiceFactory($this->getMockSlimContainer());
            $this->userServiceFactory->setDatabase($this->getMockDatabase());
        }

        return $this->userServiceFactory;
    }

    protected function getReportServiceFactory(): \Services\Report\ServiceFactory
    {
        if ($this->reportServiceFactory === null) {
            $this->reportServiceFactory = new Services\Report\ServiceFactory($this->getMockSlimContainer());
            $this->reportServiceFactory->setDatabase($this->getMockDatabase());
        }

        return $this->reportServiceFactory;
    }

    protected function getUserOrganizationEntity()
    {
        return new \Entities\Organization($this->getMockOrganizationRow());
    }

    protected function getMockOrganizationRow()
    {
        return [
            'id' => 1,
            'parent_id' => null,
            'username' => 'username',
            'password' => 'password',
            'name' => 'OrganizationTest',
            'lft' => 1,
            'rgt' => 10,
            'lvl' => 1,
            'active' => 1,
            'created_at' => '2017-12-06 01:28:09',
            'updated_at' => null,
            'unique_id' => 'organizationtest',
            'company_contact_reference' => null,
            'accounts_payable_contact_reference' => null
        ];
    }

    protected function getUserEntity()
    {
        return new \Entities\User($this->getMockUserRow());
    }

    protected function getUserEntityWithOrg()
    {
        if ($this->mockUser === null) {
            $this->mockUser = new \Entities\User($this->getMockOrganizationRow());
        }
        return $this->mockUser;
    }

    protected function getMockUserRow()
    {
        return [
            'id' => 1,
            'email_address' => 'john+smith@alldigitalrewards.com',
            'password' => 'password',
            'firstname' => 'John',
            'lastname' => 'Smith',
            'role' => 'superadmin',
            'invite_token' => null,
            'active' => 1,
            'created_at' => '2017-12-06 01:28:09',
            'updated_at' => null,
            'organization_id' => null
        ];
    }

    protected function getMockUserRowWithOrg()
    {
        return [
            'id' => 1,
            'email_address' => 'john+smith@alldigitalrewards.com',
            'password' => 'password',
            'firstname' => 'John',
            'lastname' => 'Smith',
            'role' => 'superadmin',
            'invite_token' => null,
            'active' => 1,
            'created_at' => '2017-12-06 01:28:09',
            'updated_at' => null,
            'organization_id' => 1
        ];
    }
}
