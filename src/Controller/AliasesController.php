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
     * @Route("/{id}/delete", name="alias_delete")
     * @param int $id
     * @return Response
     */
    public function deleteAlias(int $id) {

    }

    /**
     * @Route("/{id}", name="alias_edit", methods={"GET"})
     * @param int $id
     * @return Response
     */
    public function showAlias(int $id) {
        return $this->render('alias_details.html.twig', [
            'alias' => $this->aliasRepository->find($id)
        ]);
    }

    /**
     * @Route("/{id}", methods={"POST"})
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
}