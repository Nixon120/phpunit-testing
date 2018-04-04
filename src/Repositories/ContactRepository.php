<?php

namespace Repositories;

use Entities\Contact;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validator;

class ContactRepository extends BaseRepository
{
    protected $table = 'Contact';

    /**
     * @var Contact
     */
    protected $entity;

    public function getRepositoryEntity()
    {
        return Contact::class;
    }

    public function getContact($id):?Contact
    {
        $sql = "SELECT * FROM Contact WHERE reference_id = ?";
        return $this->query($sql, [$id], Contact::class);
    }

    public function save(Contact $contact)
    {
        if (!empty($contact->getId())) {
            // Update
        }

        // Insert
    }


    /**
     * @param $contact
     * @return bool
     */
    public function validate(Contact $contact)
    {
        try {
            $this->getValidator()->assert((object)$contact->toArray());
            return true;
        } catch (NestedValidationException$exception) {
            $this->errors = array_values((array)$exception->findMessages([
                'firstname',
                'lastname',
                'phone',
                'email',
                'address1',
                'city',
                'state',
                'zip' => '{{name}} must be a valid postal code for {{countryCode}}'
            ]));
            return false;
        }
    }

    /**
     * @return Validator
     */
    private function getValidator()
    {
        return Validator::attribute(
            'firstname',
            Validator::stringType()->length(1, 50)->setName('First Name')
        )->attribute(
            'lastname',
            Validator::stringType()->length(1, 50)->setName('Last Name')
        )->attribute('phone', Validator::phone()->setName('Phone'))
            ->attribute('email', Validator::email()->setName('Email'))
            ->attribute('address1', Validator::stringType()->length(1, 255)->setName('Address1'))
            ->attribute('city', Validator::stringType()->length(1, 155)->setName('City'))
            ->attribute('state', Validator::stringType()->length(2, 3)->setName('State'))
            ->attribute('zip', Validator::postalCode('US')->setName('Zip Code'));
    }
}
