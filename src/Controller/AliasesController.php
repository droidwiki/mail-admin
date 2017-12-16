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
     * @param int $id
     * @return Response
     */
    public function showAlias(int $id) {
        return $this->render('alias_details.html.twig', [
            'alias' => $this->aliasRepository->find($id),
            'title' => 'Edit alias',
        ]);
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
        if ($duplicatedAlias !== null && ($newSource !== $aliasData->getSource() || $newDestination !== $aliasData->getDestination())) {
            $this->addFlash('warning', 'There\'s already an alias with the source ' . $newSource . ' and the destination ' . $newDestination . '.');
            $isError = true;
        }

        if ($isError) {
            return $this->redirectToRoute('alias_edit', [
                'id' => $id,
            ]);
        }

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
        $info = [
            'title' => 'Create new alias',
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
        return $this->render('alias_details.html.twig', $info);
    }

    /**
     * @Route("/add", methods={"POST"})
     * @param Request $req
     * @return Response
     */
    public function saveAddAlias(Request $req) {
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
        $alreadyExistingAlias = $this->aliasRepository->findOneBy([
            'source' => $newSource,
            'destination' => $newDestination,
        ]);
        if ($alreadyExistingAlias !== null) {
            $this->addFlash('warning', 'There\'s already an alias with the source ' . $newSource . ' and the destination ' . $newDestination . '.');
            $isError = true;
        }

        if ($isError) {
            $req->getSession()->set('source', $newSource);
            $req->getSession()->set('destination', $newDestination);
            return $this->redirectToRoute('alias_add');
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