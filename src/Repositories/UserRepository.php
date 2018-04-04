<?php
namespace Repositories;

use Entities\Organization;
use Entities\User;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validator;

class UserRepository extends BaseRepository
{
    protected $table = 'User';

    public function getRepositoryEntity()
    {
        return User::class;
    }

    public function getCollectionQuery(): string
    {
        $where = " WHERE 1 = 1 ";
        if (!empty($this->getOrganizationIdContainer())) {
            $organizationIdString = implode(',', $this->getOrganizationIdContainer());
            $where = <<<SQL
WHERE User.organization_id IN ({$organizationIdString})
SQL;
        }

        return <<<SQL
SELECT User.id, Organization.unique_id as organization_reference, email_address,
  firstname, lastname, User.active, User.role, User.updated_at, User.created_at 
FROM User
LEFT JOIN Organization ON Organization.id = User.organization_id
{$where}
SQL;
    }

    //@TODO: update all references of getUser to include org (or program?) id.
    public function getUserById($primaryId)
    {

        $sql = "SELECT * FROM `User` WHERE id = ?";

        if (!empty($this->getOrganizationIdContainer())) {
            $organizationIdString = implode(',', $this->getOrganizationIdContainer());
            $sql .= <<<SQL
 AND `User`.organization_id IN ({$organizationIdString});
SQL;
        }

        if (!$user = $this->query($sql, [$primaryId], User::class)) {
            return null;
        }

        return $this->hydrateUser($user);
    }

    public function getUserByEmail($email)
    {
        $sql = "SELECT * FROM `User` WHERE email_address = ?";

        if (!$user = $this->query($sql, [$email], User::class)) {
            return null;
        }

        return $this->hydrateUser($user);
    }

    public function getUserByInviteToken($token)
    {
        $sql = "SELECT * FROM `User` WHERE invite_token = ?";

        if (!$user = $this->query($sql, [$token], User::class)) {
            return null;
        }

        return $this->hydrateUser($user);
    }

    public function isUserEmailUnique($email)
    {
        $sql = "SELECT id FROM `User` WHERE email_address = ?";

        if (!$user = $this->query($sql, [$email], User::class)) {
            return true;
        }

        return false;
    }

    private function hydrateUser(User $user)
    {
        $user->setOrganization($this->getUserOrganization($user->getOrganizationId()));
        return $user;
    }

    public function getUserOrganization(?string $id, $unique = false):?Organization
    {
        $identifier = $unique ? 'unique_id' : 'id';
        $sql = "SELECT * FROM `Organization` WHERE {$identifier} = ?";
        $args = [$id];
        return $this->query($sql, $args, Organization::class);
    }

    /**
     * @return array
     */
    public function getAuthUsers(): array
    {
        $userAuthContainer = [];

        $sql = <<<SQL
SELECT `email_address`, `password` from User WHERE active = 1
SQL;

        $users = $this->getDatabase()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        if (!empty($users)) {
            foreach ($users as $auth) {
                $userAuthContainer[$auth['email_address']] = $auth['password'];
            }
        }

        return $userAuthContainer;
    }
    
    public function validate(\Entities\User $user)
    {
        try {
            $this->getValidator($user)->assert((object) $user->toArray());
            return true;
        } catch (NestedValidationException$exception) {
            $this->errors = $exception->getMessages();
            return false;
        }
    }

    /**
     * @return Validator
     */
    private function getValidator(\Entities\User $user)
    {
        $validator = Validator::attribute('role', Validator::notEmpty()->setName('role'))
            ->attribute('email_address', Validator::notEmpty()->email()->setName('email'))
            ->attribute('password', Validator::notEmpty()->stringType()->setName('password'))
            ->attribute('firstname', Validator::notEmpty()->setName('First Name'))
            ->attribute('lastname', Validator::notEmpty()->setName('Last Name'));

        if ($user->getRole() !== 'superadmin') {
            $validator->attribute('organization_id', Validator::notEmpty()->setName('organization'));
        }

        return $validator;
    }
}
