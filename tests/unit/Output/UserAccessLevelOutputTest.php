<?php

use AllDigitalRewards\UserAccessLevelEnum\UserAccessLevelEnum;
use Entities\User;
use PHPUnit\Framework\TestCase;

class UserAccessLevelOutputTest extends TestCase
{
    public function testUserHasPiiAccessReturnsFalse()
    {
        $user = new User($this->getMockUserResultRow());
        //access level default is PII_LIMIT
        $this->assertFalse($this->hasPiiAccess($user));
    }

    public function testUserHasPiiAccessReturnsTrue()
    {
        $user = new User($this->getMockUserResultRow());
        $user->setAccessLevel(UserAccessLevelEnum::ALL);
        $this->assertTrue($this->hasPiiAccess($user));
    }

    /**
     * @param User $user
     * @return bool
     * @throws Exception
     */
    private function hasPiiAccess(User $user): bool
    {
        return !(new UserAccessLevelEnum())->isPiiLimited($user->getAccessLevel());
    }

    private function getMockUserResultRow()
    {
        return [
            'id' => 1,
            'organization_reference' => 'ORG123',
            'email_address' => 'john+smith@alldigitalrewards.com',
            'firstname' => 'John',
            'lastname' => 'Smith',
            'active' => 1,
            'role' => 'superadmin',
            'created_at' => '2017-12-06 01:28:09',
            'updated_at' => null
        ];
    }
}
