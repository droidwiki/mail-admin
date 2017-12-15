<?php

namespace App\Controller;

use App\Entity\Domain;
use App\Entity\User;
use App\Repository\DomainRepository;
use App\Repository\UserRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class DomainsController
 * @Route("/domains")
 * @package App\Controller
 */
class DomainsController extends Controller {
    /**
     * @var ObjectManager
     */
    private $entityManager;
    /**
     * @var DomainRepository
     */
    private $domainsRepository;
    /**
     * @var UserRepository
     */
    private $usersRepository;

    public function __construct(ObjectManager $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->domainsRepository = $entityManager->getRepository(Domain::class);
        $this->usersRepository = $entityManager->getRepository(User::class);
    }

    /**
     * @Route("/list", name="domains_list")
     * @return Response
     */
    public function list() {
        /** @var Domain[] $allDomains */
        $allDomains = $this->domainsRepository->findAll();
        $domainAddresses = [];
        foreach ($allDomains as $domain) {
            $users = $this->usersRepository->findByDomain($domain);
            $domainAddresses[$domain->getDomain()] = $users;
        }
        return $this->render('domains_list.html.twig', [
            'domainAddresses' => $domainAddresses
        ]);
    }

    /**
     * @Route("/{id}", name="domain_details", methods={"GET"})
     * @param string $id
     * @return Response
     */
    public function showDomain(string $id) {
        $domainData = $this->domainsRepository->find($id);
        return $this->render('domain_details.html.twig', [
            'domain' => $domainData,
        ]);
    }

    /**
     * @Route("/{id}", methods={"POST"})
     * @param Request $req
     * @param string $id
     * @return Response
     */
    public function editDomain(Request $req, string $id) {
        /** @var Domain $changedDomain */
        $changedDomain = $this->domainsRepository->find($id);
        $newDomain = new Domain($req->get('domain'));
        $duplicatedDomain = $this->domainsRepository->find($newDomain->getDomain());

        $isError = false;
        if ($duplicatedDomain !== null && $id !== $newDomain->getDomain()) {
            $this->addFlash('warning', 'The new domain-name ' . $newDomain->getDomain() . ' already exists.');
            $isError = true;
        }

        if ($newDomain->getDomain() === '') {
            $this->addFlash('warning', 'The new domain-name can not be empty.');
            $isError = true;
        }

        if ($isError) {
            return $this->redirectToRoute('domain_details', [
                'id' => $id,
            ]);
        }

        /** @var User[] $domainUsers */
        $domainUsers = $this->usersRepository->findByDomain($changedDomain);

        foreach ($domainUsers as $user) {
            $user->setDomain($newDomain);
            $this->entityManager->persist($user);
        }

        $changedDomain->setDomain($newDomain->getDomain());
        $this->entityManager->persist($changedDomain);

        $this->entityManager->flush();
        $this->addFlash('success', 'The domain-name was changed.');

        return $this->redirectToRoute('domains_list');
    }
}
