<?php
namespace Services;

use AllDigitalRewards\Services\Catalog\Client;
use Entities\User;
use Events\EventPublisherFactory;
use Google\Cloud\Storage\StorageClient;
use Interop\Container\ContainerInterface;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Repositories\OrganizationRepository;
use Repositories\ParticipantRepository;
use Repositories\ProgramRepository;
use Repositories\ProgramTypeRepository;
use Services\Authentication\Authenticate;
use Slim\Flash\Messages;
use Superbalist\Flysystem\GoogleStorage\GoogleStorageAdapter;

abstract class AbstractServiceFactory
{

    /** @var \PDO */
    private $database;

    private $container;

    /**
     * @var GoogleStorageAdapter|Local
     */
    private $storageAdapter;

    /**
     * @var ProgramRepository
     */
    private $programRepository;

    /**
     * @var ProgramTypeRepository
     */
    private $programTypeRepository;

    private $organizationRepository;

    private $participantRepository;

    private $authenticatedUser;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    public function setDatabase(\PDO $database)
    {
        $this->database = $database;
    }

    public function getDatabase():\PDO
    {
        if ($this->database === null) {
            $this->database = $this->container->get('database');
        }

        return $this->database;
    }

    public function getEventPublisher()
    {
        $eventPublisherFactory = new EventPublisherFactory($this->container);
        return $eventPublisherFactory();
    }

    public function getFilesystem(string $folder)
    {
        if (getenv('FILESYSTEM') === 'local') {
            return $this->getLocalFilesystem($folder);
        }

        return $this->getCdnFilesystem($folder);
    }

    public function getLocalFilesystem($folder)
    {
        if (!file_exists(ROOT . '/public/resources/app/' . $folder)) {
            mkdir(ROOT . '/public/resources/app/' . $folder, 0755, true);
        }

        $this->storageAdapter = new Local(ROOT . '/public/resources/app/' . $folder);
        return new Filesystem($this->storageAdapter);
    }

    public function getCdnFilesystem($folder)
    {
        if ($this->storageAdapter === null) {
            $storageClient = new StorageClient([
                'projectId' => getenv('GOOGLE_PROJECT_ID'),
                'keyFile' => json_decode(getenv('GOOGLE_CDN_KEY'), true),
            ]);

            $bucketName = getenv('GOOGLE_CDN_BUCKET');
            $bucket = $storageClient->bucket($bucketName);

            $this->storageAdapter = new GoogleStorageAdapter($storageClient, $bucket);
            $this->storageAdapter ->setPathPrefix($folder . '/');
        }

        return new Filesystem($this->storageAdapter);
    }

    public function getFlashMessenger(): Messages
    {
        return $this->container->get('flash');
    }

    public function getAuthenticatedUser(): ?User
    {
        if ($this->authenticatedUser === null) {
            $this->authenticatedUser = $this->getContainer()->get('authentication')->getUser();
        }

        return $this->authenticatedUser;
    }

    public function setAuthenticatedUser(User $user)
    {
        $this->authenticatedUser = $user;
    }

    public function getOrganizationRepository(): OrganizationRepository
    {
        if ($this->organizationRepository === null) {
            $user = $this->getAuthenticatedUser();
            $this->organizationRepository = new OrganizationRepository($this->getContainer()->get('database'));
            if ($user !== null) {
                $this->organizationRepository->setProgramIdContainer($user->getProgramOwnershipIdentificationCollection());
                $this->organizationRepository->setOrganizationIdContainer($user->getOrganizationOwnershipIdentificationCollection());
            }
        }

        return $this->organizationRepository;
    }

    public function getProgramRepository(): ProgramRepository
    {
        if ($this->programRepository === null) {
            $user = $this->getAuthenticatedUser();
            $this->programRepository = new ProgramRepository($this->getDatabase(), $this->getCatalogService(), $this->getFilesystem('layout'));
            if ($user !== null) {
                $this->programRepository->setProgramIdContainer($user->getProgramOwnershipIdentificationCollection());
                $this->programRepository->setOrganizationIdContainer($user->getOrganizationOwnershipIdentificationCollection());
            }
        }

        return $this->programRepository;
    }

    public function getProgramTypeRepository(): ProgramTypeRepository
    {
        if ($this->programTypeRepository === null) {
            $this->programTypeRepository = new ProgramTypeRepository($this->getDatabase());
        }

        return $this->programTypeRepository;
    }

    public function getParticipantRepository(): ParticipantRepository
    {
        if ($this->participantRepository === null) {
            $user = $this->getAuthenticatedUser();
            $this->participantRepository = new ParticipantRepository($this->getDatabase(), $this->getCatalogService());
            if ($user !== null) {
                $this->participantRepository->setProgramIdContainer($user->getProgramOwnershipIdentificationCollection());
                $this->participantRepository->setOrganizationIdContainer($user->getOrganizationOwnershipIdentificationCollection());
            }
        }

        return $this->participantRepository;
    }

    public function getCatalogService(): Client
    {
        $client = new \AllDigitalRewards\Services\Catalog\Client;
        $client->setUrl(getenv('CATALOG_URL'));
        $client->setToken($this->getAuthenticatedTokenString());

        return $client;
    }

    public function getAuthenticatedTokenString(): ?string
    {
        /** @var Authenticate $auth */
        $auth = $this->getContainer()->get('authentication');

        return $auth->getToken()->getToken();
    }
}
