<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\Type\UserType;
use App\Helpers\Utils;
use App\Service\MessageGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class UserController extends AbstractController
{
    protected $slugger;
    protected $encoder;

    function __construct(SluggerInterface $slugger, UserPasswordEncoderInterface $encoder)
    {
        $this->slugger = $slugger;
        $this->encoder = $encoder;
    }

    /**
     * @Route("/{_locale}/profile", name="_app_profile")
     */
    public function index(Request $request, UserPasswordEncoderInterface $encoder)
    {
        $message = '';
        $oldPassword = $this->getUser()->getPassword();
        $form = $this->createForm(UserType::class, $this->getUser());
        if ($request->isMethod('POST')) {
            $form->submit($request->request->get($form->getName()));
            if ($form->isSubmitted() && $form->isValid()) {
                $user = $form->getData();
                $this->setPassword($user, $oldPassword);
                $this->saveUserInDB($user);
                $message = '<div class="alert alert-success">User updated!</div>';
            }
        }

        return $this->render('user/profile.html.twig', [
            'form' => $form->createView(),
            'message' => $message
        ]);
    }

    function uploadFile(Request $request, $userId = 0)
    {
        $result = new \stdClass();
        $alloweds = array('png', 'PNG', 'jpg', 'jpeg');
        $result->photoName = "";
        $result->error = "";
        $files = $request->files->get('user');
        if (!is_object($files['photo'])) {
            return $result;
        }

        foreach ($files as $file) {
            if (in_array($file->guessExtension(), $alloweds)) {
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $this->slugger->slug($originalFilename);
                $newFilename = $safeFilename . '.' . $file->guessExtension();
                $result->photoName = $newFilename;
                $imgPath = $this->getParameter('image_directory') . '/' . $userId . '/';
                try {
                    $file->move(
                        $imgPath,
                        $newFilename
                    );
                } catch (FileException $e) {
                    $result->error = '<div class="alert alert-danger">' . $e . '</div>';
                }
            } else {
                $result->error = '<b>Error:</b> File type not allowed';
            }
        }

        return $result;
    }

    /**
     * @Route("/{_locale}/admin/add-user", name="_add_user")
     */
    public function addUser(Request $request)
    {
        $message = '';
        $form = $this->createForm(UserType::class, new User());
        if ($request->isMethod('POST')) {
            $form->submit($request->request->get($form->getName()));
            if ($form->isSubmitted() && $form->isValid()) {
                $user = $form->getData();
                $password = $user->getPassword();
                $user->setPassword($this->encoder->encodePassword($user, $password));
                $userId = $this->saveUserInDB($user);
                $form = $this->createForm(UserType::class, new User());
                $message = '<div class="alert alert-success">User created! Id: ' . $userId . '</div>';
            }
        }

        return $this->render('user/add-user.html.twig', ['form' => $form->createView(), 'message' => $message]);
    }

    /**
     * @Route("/{_locale}/admin/edit-user/{id}", name="_edit_user")
     */
    function editUser(Request $request, $id)
    {
        $message = '';
        $user = $this->getDoctrine()->getRepository(User::class)->findBy(array('id' => $id));
        $userObject = $user[0];
        $this->changeSuperAdmin($userObject);
        $oldPassword = $userObject->getPassword();
        $form = $this->createForm(UserType::class, $user[0]);

        if ($request->isMethod('POST')) {
            $form->submit($request->request->get($form->getName()));
            if ($form->isSubmitted() && $form->isValid()) {
                $uploadResult = $this->uploadFile($request, $id);
                $message .= $uploadResult->error;
                if ($uploadResult->error == "") {
                    $photo = $uploadResult->photoName != "" ? $uploadResult->photoName : $userObject->getPhoto();
                }
                $user = $form->getData();
                $user = $this->setPassword($user, $oldPassword);
                $user->setPhoto($photo);
                $this->saveUserInDB($user);
                $message .= '<div class="alert alert-success">User updated!</div>';
            }
        }

        return $this->render('user/add-user.html.twig', [
            'form' => $form->createView(),
            'message' => $message,
            'user' => is_array($user) ? $user[0] : $user,
        ]);
    }

    function changeSuperAdmin($userObject)
    {
        if (User::hasRole($userObject->getRoles(), 'ROLE_SUPER_ADMIN')) {
            if (!User::hasRole($this->getUser()->getRoles(), 'ROLE_SUPER_ADMIN')) {
                $message = "You don't have privileges to perfom this operation.";
                return $this->render('common/unauthorized.html.twig', array('message' => $message));
            }
        }
    }

    function setPassword($user, $oldPassword)
    {
        if ($user->getPassword() == "") {
            $user->setPassword($oldPassword);
        } else {
            $user->setPassword($this->encoder->encodePassword($user, $user->getPassword()));
        }

        return $user;
    }

    public function pre($obj)
    {
        echo '<pre>';
        print_r($obj);
        echo '</pre>';
    }

    private function saveUserInDB(User $userObject)
    {
        $manager = $this->getDoctrine()->getManager();
        $manager->persist($userObject);
        $manager->flush();
        return $userObject->getId();
    }

    public function getUsers()
    {
        return $this->getDoctrine()->getRepository(User::class)->findAll();
    }

    /**
     * @Route("/admin/users", name="_json_users")
     */
    public function jsonUsers()
    {
        $users = $this->getUsers();
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $response->setContent($this->serializerToJson($users));

        return $response;
    }

    public function serializerToJson($anObject)
    {
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);

        return $serializer->serialize($anObject, 'json');
    }

    /**
     * @Route("/{_locale}/admin/list-users", name="_list_users")
     */
    public function listUsers(): Response
    {
        $users = $this->getUsers();
        return $this->render('user/users.html.twig', array(
                'users' => $users,
            )
        );
    }

    /**
     * @Route("/config/create-admin", name="_create_admin")
     */
    public function createAdminUser(UserPasswordEncoderInterface $encoder)
    {
        $users = $this->getDoctrine()->getRepository(User::class)->findAll();
        $response = '<span style="color: red;"><b>El usuario admin ya existe.</b></span>';
        if (count($users) == 0) {
            $response = '<span style="color: red;"><b>Usuario admin creado correctamente.</b></span>';
            $user = new User();
            $manager = $this->getDoctrine()->getManager();
            $password = $encoder->encodePassword($user, 'z0002cf576edb');
            $user->setFirstname('Admin');
            $user->setLastname('Admin');
            $user->setEmail('admin@admin.com');
            $user->setPassword($password);
            $user->setRoles(['ROLE_SUPER_ADMIN']);
            $user->setPhoto('');
            $manager->persist($user);
            $manager->flush();
        }

        return new Response($response);
    }

    /**
     * @Route("/test-message", name="_test_message")
     */
    function testService(MessageGenerator $generator)
    {
        return new Response($generator->getHappyMessage());
    }

    /**
     * @Route("/set-locale/{locale}", name="_set_locale")
     */
    function setLocale(Request $request, $locale = "")
    {
        $locale = "/" . $locale . "/";
        $referer = $request->headers->get('referer');
        $explode = explode("/", $referer);

        if (count($explode) == 4) {
            return new RedirectResponse($locale);
        }

        $explodedLocale = isset($explode[3]) ? $explode[3] : "";
        $currentLocale = "/" . $explodedLocale . "/";
        $referer = str_ireplace($currentLocale, $locale, $referer);

        return new RedirectResponse($referer);
    }
}
