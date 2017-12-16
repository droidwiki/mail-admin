<?php
/**
 * Created by IntelliJ IDEA.
 * User: florian
 * Date: 15.12.17
 * Time: 22:50
 */

namespace App\Controller;

use App\Entity\Alias;
use App\Repository\AliasRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class AliasesController
 * @Route("/aliases")
 * @package App\Controller
 */
class AliasesController extends Controller
{
    /**
     * @var AliasRepository
     */
    private $aliasRepository;
    /**
     * @var ObjectManager
     */
    private $entityManager;

    public function __construct(ObjectManager $em)
    {
        $this->entityManager = $em;
        $this->aliasRepository = $em->getRepository(Alias::class);
    }

    /**
     * @param Request $req
     * @param string $title
     * @param int|null $id
     * @return array
     */
    private function getAliasDetailsInfo(Request $req, string $title, int $id = null)
    {
        $info = [
            'title' => $title,
        ];
        if ($req->getSession()->has('source')) {
            $info['alias'] = [
                'source' => $req->getSession()->remove('source'),
            ];
        }
        if ($req->getSession()->has('destination')) {
            if (!isset($info['alias'])) {
                $info['alias'] = [];
            }
            $info['alias']['destination'] = $req->getSession()->remove('destination');
        }
        if (!isset($info['alias']) && $id !== null) {
            $info['alias'] = $this->aliasRepository->find($id);
        }

        return $info;
    }

    /**
     * @param Request $req
     * @param array $redirectParams An array of parameters to the redirectToRoute method. The first value is the name of
     *  the route, the second is an array of optional parameters needed for the route.
     * @param Alias $aliasData
     * @return null|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    private function validateModifyAliases(Request $req, array $redirectParams, Alias $aliasData = null) {
        $newSource = $req->get('source');
        $newDestination = $req->get('destination');
        $isError = false;
        if ($newSource === '') {
            $this->addFlash('warning', 'The source e-mail address can not be empty.');
            $isError = true;
        }
        if ($newDestination === '') {
            $this->addFlash('warning', 'The destination e-mail address can not be empty.');
            $isError = true;
        }
        $duplicatedAlias = $this->aliasRepository->findOneBy([
            'source' => $newSource,
            'destination' => $newDestination,
        ]);
        $changed = true;
        if ($aliasData !== null) {
            $changed = $newSource !== $aliasData->getSource() || $newDestination !== $aliasData->getDestination();
        }
        if ($duplicatedAlias !== null && $changed) {
            $this->addFlash('warning', 'There\'s already an alias with the source ' . $newSource . ' and the destination ' . $newDestination . '.');
            $isError = true;
        }

        if ($isError) {
            $req->getSession()->set('source', $newSource);
            $req->getSession()->set('destination', $newDestination);
            return call_user_func_array([$this, 'redirectToRoute'], $redirectParams);
        }
    }

    /**
     * @Route("/list", name="aliases_list")
     * @return Response
     */
    public function list() {
        return $this->render('aliases_list.html.twig', [
            'aliases' => $this->aliasRepository->findAll(),
        ]);
    }

    /**
     * @Route("/{id}/delete", name="alias_delete", methods={"POST"}, requirements={"id"="\d+"})
     * @param int $id
     * @return Response
     */
    public function deleteAlias(int $id) {
        $aliasData = $this->aliasRepository->find($id);

        $this->entityManager->remove($aliasData);
        $this->entityManager->flush();

        $this->addFlash('success', 'The alias was deleted.');
        return $this->redirectToRoute('aliases_list');
    }

    /**
     * @Route("/{id}", name="alias_edit", methods={"GET"}, requirements={"id"="\d+"})
     * @param Request $req
     * @param int $id
     * @return Response
     */
    public function showAlias(Request $req, int $id) {
        return $this->render('alias_details.html.twig', $this->getAliasDetailsInfo($req, 'Edit alias', $id));
    }

    /**
     * @Route("/{id}", methods={"POST"}, requirements={"id"="\d+"})
     * @param Request $req
     * @param int $id
     * @return Response
     */
    public function editAlias(Request $req, int $id) {
        /** @var Alias $aliasData */
        $aliasData = $this->aliasRepository->find($id);

        $redirect = $this->validateModifyAliases(
            $req,
            [
                'alias_edit', [
                    'id' => $id,
                ]
            ], $aliasData);
        if ($redirect !== null) {
            return $redirect;
        }
        $newSource = $req->get('source');
        $newDestination = $req->get('destination');
        $aliasData->setSource($newSource);
        $aliasData->setDestination($newDestination);

        $this->entityManager->persist($aliasData);
        $this->entityManager->flush();

        $this->addFlash('success', 'The alias was saved.');

        return $this->redirectToRoute('aliases_list');
    }

    /**
     * @Route("/add", name="alias_add", methods={"GET"})
     * @param Request $req
     * @return Response
     */
    public function showAddAlias(Request $req) {
        return $this->render('alias_details.html.twig', $this->getAliasDetailsInfo($req, 'Create new alias'));
    }

    /**
     * @Route("/add", methods={"POST"})
     * @param Request $req
     * @return Response
     */
    public function saveAddAlias(Request $req) {
        $newSource = $req->get('source');
        $newDestination = $req->get('destination');

        $redirect = $this->validateModifyAliases(
            $req,
            ['alias_add']
        );
        if ($redirect !== null) {
            return $redirect;
        }

        $alias = new Alias();
        $alias->setSource($newSource);
        $alias->setDestination($newDestination);

        $this->entityManager->persist($alias);
        $this->entityManager->flush();

        $this->addFlash('success', 'The alias was saved.');
        return $this->redirectToRoute('aliases_list');
    }
}