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
     * @param Request $req
     * @param string $title
     * @param string|null $id
     * @return array
     */
    private function getDomainDetailsInfo(Request $req, string $title, string $id = null)
    {
        $info = [
            'title' => $title,
        ];
        if ($req->getSession()->has('domain')) {
            $info['domain'] = [
                'domain' => $req->getSession()->remove('domain'),
            ];
        }
        if (!isset($info['domain']) && $id !== null) {
            $info['domain'] = $this->domainsRepository->find($id);
        }

        return $info;
    }

    /**
     * @param Request $req
     * @param array $redirectParams An array of parameters to the redirectToRoute method. The first value is the name of
     *  the route, the second is an array of optional parameters needed for the route.
     * @param Domain $domainData
     * @return null|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    private function validateModifyDomains(Request $req, array $redirectParams, Domain $domainData = null) {
        $newDomain = $req->get('domain');
        $isError = false;
        if ($newDomain === '') {
            $this->addFlash('warning', 'The new domain-name can not be empty.');
            $isError = true;
        }
        $duplicatedDomain = $this->domainsRepository->find($newDomain);
        $changed = true;
        if ($domainData !== null) {
            $changed = $newDomain !== $domainData->getDomain();
        }
        if ($duplicatedDomain !== null && $changed) {
            $this->addFlash('warning', 'The new domain-name ' . $newDomain . ' already exists.');
            $isError = true;
        }

        if ($isError) {
            $req->getSession()->set('domain', $newDomain);
            return call_user_func_array([$this, 'redirectToRoute'], $redirectParams);
        }
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
     * @Route("/add", name="domain_add", methods={"GET"})
     * @param Request $req
     * @return Response
     */
    public function showAddDomain(Request $req) {
        return $this->render('domain_details.html.twig', $this->getDomainDetailsInfo($req, 'Create domain'));
    }

    /**
     * @Route("/add", methods={"POST"})
     * @param Request $req
     * @return Response
     */
    public function saveAddDomain(Request $req) {
        $newDomain = new Domain($req->get('domain'));

        $redirect = $this->validateModifyDomains($req, ['domain_add']);

        if ($redirect !== null) {
            return $redirect;
        }

        $this->entityManager->persist($newDomain);

        $this->entityManager->flush();
        $this->addFlash('success', 'The domain was added.');

        return $this->redirectToRoute('domains_list');
    }

    /**
     * @Route("/{id}", name="domain_details", methods={"GET"})
     * @param Request $req
     * @param string $id
     * @return Response
     */
    public function showDomain(Request $req, string $id) {
        return $this->render('domain_details.html.twig', $this->getDomainDetailsInfo($req, 'Edit domain', $id));
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

        $redirect = $this->validateModifyDomains($req, ['domain_details', ['id' => $id]], $changedDomain);

        if ($redirect !== null) {
            return $redirect;
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
