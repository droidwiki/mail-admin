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
     * @param Request $req
     * @param string $title
     * @param string|null $id
     * @return array
     */
    private function getUserDetailsInfo(Request $req, string $title, string $id = null)
    {
        $info = [
            'title' => $title,
            'domains' => $this->domainsRepository->findAll(),
        ];
        if ($req->getSession()->has('username')) {
            $info['user'] = new User();
            $info['user']->setUsername($req->getSession()->remove('username'));
        }
        if ($req->getSession()->has('domain')) {
            if (!isset($info['user'])) {
                $info['user'] = new User();
            }
            $info['user']->setDomain(new Domain($req->getSession()->remove('domain')));
        }
        if (!isset($info['user']) && $id !== null) {
            $info['user'] = $this->usersRepository->find($id);
        }

        return $info;
    }

    /**
     * @param Request $req
     * @param array $redirectParams An array of parameters to the redirectToRoute method. The first value is the name of
     *  the route, the second is an array of optional parameters needed for the route.
     * @param User $userData
     * @return null|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    private function validateModifyUser(Request $req, array $redirectParams, User $userData = null) {
        $newUsername = $req->get('username');
        $newDomain = $req->get('domain');
        $newPassword = $req->get('password');
        $isError = false;
        $domain = $this->domainsRepository->find($newDomain);
        if ($domain === null) {
            $this->addFlash('warning', 'Domain ' . $newDomain . ' is not valid');
            $isError = true;
        }

        if ($userData === null || $userData->getUsername() !== $newUsername) {
            $duplicateUser = $this->usersRepository->findOneByUsername($newUsername, $newDomain);
            if ($duplicateUser !== null) {
                $this->addFlash('warning', 'The username ' . $newUsername . ' already exists in the domain ' . $newDomain . '.');
                $isError = true;
            }
        }

        if ($newUsername === '') {
            $this->addFlash('warning', 'The username can not be empty');
            $isError = true;
        }
        if ($newDomain === '') {
            $this->addFlash('warning', 'The domain-name can not be empty.');
            $isError = true;
        }
        if ($userData === null && $newPassword === '') {
            $this->addFlash('warning', 'The password can not be empty.');
            $isError = true;
        }

        if ($isError) {
            $req->getSession()->set('username', $newUsername);
            $req->getSession()->set('domain', $newDomain);
            return call_user_func_array([$this, 'redirectToRoute'], $redirectParams);
        }
    }

    /**
     * @Route("/add", name="user_add", methods={"GET"})
     * @param Request $req
     * @return Response
     */
    public function showAddUser(Request $req) {
        return $this->render('user_details.html.twig', $this->getUserDetailsInfo($req, 'Add user'));
    }

    /**
     * @Route("/add", methods={"POST"})
     * @param Request $req
     * @return Response
     */
    public function saveAddUser(Request $req) {
        $redirect = $this->validateModifyUser($req, ['user_add']);

        if ($redirect !== null) {
            return $redirect;
        }
        $user = new User();
        $user->setDomain(new Domain($req->get('domain')));
        $user->setUsername($req->get('username'));
        $user->setPassword($req->get('password'));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->addFlash('success', 'The user was added.');
        return $this->redirectToRoute('domains_list');
    }

    /**
     * @Route("/{id}/delete", name="user_delete", methods={"POST"})
     * @param int $id
     * @return Response
     */
    public function deleteUser(int $id) {
        $userData = $this->usersRepository->find($id);

        $this->entityManager->remove($userData);
        $this->entityManager->flush();

        $this->addFlash('success', 'The user was deleted.');
        return $this->redirectToRoute('domains_list');
    }

    /**
     * @Route("/{id}", name="user", methods={"GET"})
     * @param Request $req
     * @param int $id
     * @return Response
     */
    public function showUser(Request $req, int $id) {
        return $this->render('user_details.html.twig', $this->getUserDetailsInfo($req, 'Edit user', $id));
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