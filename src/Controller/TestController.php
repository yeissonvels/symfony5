<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class TestController extends AbstractController {
    /**
     * @Route("/", name="/");
     */
    public function SayHello() : Response {
        $message = 'Hello World from controller!!!';
        /*return new Response(
            'Hello World'
        );*/
        return $this->render('test/hello.html.twig', array('message' => $message));
    }

    /**
     * @Route("/test-redirect", name="test-redirect");
     */
    public function testRedirect(): RedirectResponse {
        return $this->redirectToRoute('/', array(), 301);
        //return $this->redirect('http://symfonyproject/');
    }

    /**
     * @Route("/login", name="login");
     */
    public function testLogging(LoggerInterface $logger) {
        $logger->info('We are logging!');
        //return new Response("ok");
    }
}