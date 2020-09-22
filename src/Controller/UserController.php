<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\Type\UserType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Serializer;

class UserController extends AbstractController
{
    /**
     * @Route("/user", name="user")
     */
    public function index()
    {
        return $this->render('user/index.html.twig', [
            'controller_name' => 'UserController',
        ]);
    }

    /**
     * @Route("/add-user", name="add-user")
     */
    public function addUser(Request $request) {
        $user = new User();
        $message = '';
        $user = $this->setProperties($request, $user);
        $form = $this->createForm(UserType::class, $user);

        if ($request->isMethod('POST')) {
            $form->submit($request->request->get($form->getName()));
            if ($form->isSubmitted() && $form->isValid()) {
                $password = $user->getPassword();
                $user->setPassword(md5($password));
                $userId = $this->saveUserInDB($user);
                $form = $this->createForm(UserType::class, new User());
                $message = '<div class="alert alert-success">Usuario creado! Id: ' . $userId . '</div>';
            }
        }

        return $this->render( 'user/add-user.html.twig', ['form' => $form->createView(), 'message' => $message]);
    }

    private function saveUserInDB(User $userObject) {
        $manager = $this->getDoctrine()->getManager();
        $manager->persist($userObject);
        $manager->flush();
        return $userObject->getId();
    }

    private function setProperties(Request $request, User $obj) {
        if (!empty($request->request->get('user'))) {
            foreach ($request->request->get('user') as $key => $value) {
                if (property_exists($obj, $key)) {
                    $key = str_replace('_', '', $key);
                    $userArray = array($obj, "set" . ucfirst($key));
                    $arrValue = array($value);
                    call_user_func_array($userArray, $arrValue);
                }
            }
        }

        return $obj;
    }

    /**
     * @Route("/get-users", name="user-list")
     */
    public function getUsers(): Response {
        //$manager = $this->getDoctrine()->getManager();
        $data = $this->getDoctrine()->getRepository(User::class)->findAll();
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $response->setContent($this->serializerToJson($data));

        return $response;
    }

    public function serializerToJson($anObject) {
        $serializer = new Serializer();
        return $serializer->serialize($anObject);
    }
}
