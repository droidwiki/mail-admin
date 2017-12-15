<?php

namespace App\EventSubscriber;

use App\Controller\AliasesController;
use App\Controller\DomainsController;
use App\Controller\UserController;
use App\Entity\Alias;
use App\Entity\Domain;
use App\Entity\User;
use App\Repository\AliasRepository;
use App\Repository\DomainRepository;
use App\Repository\UserRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class RequiresExistingUserDomainSubscriber implements EventSubscriberInterface {
    /**
     * @var Request
     */
    private $request;
    /**
     * @var UserRepository
     */
    private $userRepository;
    /**
     * @var DomainRepository
     */
    private $domainRepository;
    /**
     * @var AliasRepository
     */
    private $aliasRepository;

    public function __construct(ObjectManager $manager, RequestStack $requestStack) {
        $this->userRepository = $manager->getRepository(User::class);
        $this->domainRepository = $manager->getRepository(Domain::class);
        $this->aliasRepository = $manager->getRepository(Alias::class);
        $this->request = $requestStack->getCurrentRequest();
    }

    public function onKernelController(FilterControllerEvent $event) {
        $controller = $event->getController();

        /*
         * $controller passed can be either a class or a Closure.
         * This is not usual in Symfony but it may happen.
         * If it is a class, it comes in array format
         */
        if (!is_array($controller)) {
            return;
        }

        $repository = null;
        $findBy = null;
        $errorMessage = 'The requested page could not be found.';
        if ($controller[0] instanceof UserController) {
            if (!$this->request->attributes->has('id')) {
                return;
            }
            $findBy = $this->request->attributes->get('id');
            $repository = $this->userRepository;
            $errorMessage = 'The user with the UserID ' . $findBy . ' does not exist.';
        }

        if ($controller[0] instanceof DomainsController) {
            if (!$this->request->attributes->has('id')) {
                return;
            }
            $findBy = $this->request->attributes->get('id');
            $repository = $this->domainRepository;
            $errorMessage = 'The domain ' . $findBy . ' does not exist.';
        }

        if ($controller[0] instanceof AliasesController) {
            if (!$this->request->attributes->has('id')) {
                return;
            }
            $findBy = $this->request->attributes->get('id');
            $repository = $this->aliasRepository;
            $errorMessage = 'The alias with the ID ' . $findBy . ' does not exist.';
        }

        if ($repository !== null && $findBy !== null) {
            if ($repository->find($findBy) === null)
                throw new NotFoundHttpException($errorMessage);
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }
}