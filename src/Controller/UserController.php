<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\Type\UserType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class UserController extends AbstractController
{
    /**
     * @Route("/profile", name="app_profile")
     */
    public function index()
    {
        return $this->render('user/profile.html.twig', [
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

    /**
     * @Route("/profile/edit-user/{id}", name="edit-user")
     */
    function editUser(Request $request, $id) {
        $user = $this->getDoctrine()->getRepository(User::class)->findBy(array('id' => $id));
        $this->pre($user);
        return new Response("Ok");
    }

    public function pre($obj) {
        echo '<pre>';
        print_r($obj);
        echo '</pre>';
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

    public function getUsers() {
        return $this->getDoctrine()->getRepository(User::class)->findAll();
    }

    /**
     * @Route("/profile/users", name="json-users")
     */
    public function jsonUsers() {
        $users = $this->getUsers();
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $response->setContent($this->serializerToJson($users));

        return $response;
    }

    public function serializerToJson($anObject) {
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);

        return $serializer->serialize($anObject, 'json');
    }

    /**
     * @Route("/profile/list-users", name="list-users")
     */
    public function listUsers(): Response {
        $users = $this->getUsers();
        $this->pre($users);
        return $this->render('user/users.html.twig', array('users' => $users));
    }
}
