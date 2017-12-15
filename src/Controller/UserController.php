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
 * Class UserController
 * @Route("/user")
 * @package App\Controller
 */
class UserController extends Controller {
    /**
     * @var ObjectManager
     */
    private $entityManager;
    /**
     * @var UserRepository
     */
    private $usersRepository;
    /**
     * @var DomainRepository
     */
    private $domainsRepository;

    public function __construct(ObjectManager $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->usersRepository = $entityManager->getRepository(User::class);
        $this->domainsRepository = $entityManager->getRepository(Domain::class);
    }

    /**
     * @Route("/{id}", name="user", methods={"GET"})
     * @param int $id
     * @return Response
     */
    public function showUser(int $id) {
        $userData = $this->usersRepository->find($id);

        return $this->render('user_details.html.twig', [
            'user' => $userData,
            'domains' => $this->domainsRepository->findAll()
        ]);
    }

    /**
     * @Route("/{id}", methods={"POST"})
     * @param  Request $req
     * @param int $id
     * @return Response
     */
    public function editUser(Request $req, int $id) {
        /** @var User $userData */
        $userData = $this->usersRepository->find($id);

        $changedUsername = $req->get('username');
        $changedDomain = $req->get('domain');
        $isError = false;
        $domain = $this->domainsRepository->find($changedDomain);
        if ($domain === null) {
            $this->addFlash('warning', 'Domain ' . $changedDomain . ' is not valid');
            $isError = true;
        }

        if ($userData->getUsername() !== $changedUsername) {
            $duplicateUser = $this->usersRepository->findOneByUsername($changedUsername, $changedDomain);
            if ($duplicateUser !== null) {
                $this->addFlash('warning', 'The new username ' . $changedUsername . ' already exists in the domain ' . $changedDomain . '.');
                $isError = true;
            }
        }

        if ($changedUsername === '') {
            $this->addFlash('warning', 'The new username can not be empty');
            $isError = true;
        }

        if (!$isError) {
            $userData->setUsername($changedUsername);
            $userData->setDomain(new Domain($changedDomain));

            $this->entityManager->persist($userData);
            $this->entityManager->flush();

            $this->addFlash('success', 'The user data was saved.');
        }

        return $this->redirectToRoute('user', [
            'id' => $id,
        ]);
    }

    /**
     * @Route("/{id}/reset", name="reset_password", methods={"GET"})
     * @param int $id
     * @return Response
     */
    public function showResetPassword(int $id) {
        $userData = $this->usersRepository->find($id);

        return $this->render('user_reset_password.html.twig', [
            'user' => $userData,
        ]);
    }

    /**
     * @Route("/{id}/reset", methods={"POST"})
     * @param  Request $req
     * @param int $id
     * @return Response
     */
    public function editResetPassword(Request $req, int $id) {
        /** @var User $userData */
        $userData = $this->usersRepository->find($id);

        $changedPassword = $req->get('password');

        if ($changedPassword === '') {
            $changedPassword = null;
        }

        $userData->setPassword($changedPassword);

        $this->entityManager->persist($userData);
        $this->entityManager->flush();

        $this->addFlash('success', 'The new password was saved.');

        return $this->redirectToRoute('user', [
            'id' => $id,
        ]);
    }
}