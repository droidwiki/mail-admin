<?php
/**
 * Created by IntelliJ IDEA.
 * User: florian
 * Date: 15.12.17
 * Time: 22:15
 */

namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends Controller
{
    /**
     * @Route("/")
     */
    public function showIndex() {
        return $this->redirectToRoute('domains_list');
    }
}