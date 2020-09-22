<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApiController extends AbstractController
{
    /**
     * @Route("/api/{option}", name="api", methods={"GET", "HEAD"})
     */
    public function index($option)
    {
        $jsonResponse = array('error' => 'Opción incorrecta!');
        $response = new Response();
        $request = new Request();
        $response->headers->set('Content-Type', 'application/json');

        $users = array();
        $cities = array('Medellín', 'Madrid', 'Paris', 'Múnich');

        for ($i = 1; $i < 10; $i++) {
            $users[] = "User" . $i;
        }

        if ($option == "users") {
            $jsonResponse = $users;
        } else if ($option == "cities") {
            $jsonResponse = $cities;
        }

        $json = (String) json_encode($jsonResponse);
        return $response->setContent($json);
    }

    public function pre($obj) {
        echo '<pre>';
        print_r($obj);
        echo '</pre>';
    }
}
