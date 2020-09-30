<?php

namespace App\Controller;

use App\Service\FlagService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomeController extends AbstractController {
    /**
     * @Route("/{_locale}/", name="_home", defaults={"_locale" = "en"}, requirements={"_locale" = "en|de|es"});
     * @Route("/")
     */
    public function SayHello() : Response {
        return $this->render('home/home.html.twig');
    }
}