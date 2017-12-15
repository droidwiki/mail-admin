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

    public function __construct(ObjectManager $em)
    {
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
}