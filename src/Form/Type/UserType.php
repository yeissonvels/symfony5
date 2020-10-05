<?php
namespace App\Form\Type;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Contracts\Translation\TranslatorInterface;

class  UserType extends AbstractType {
    protected $translator;
    function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
        ->add('firstname', TextType::class, array(
                'label' => $this->translator->trans('firstname')
            )
        )
        ->add('lastname', TextType::class, array(
                'label' => $this->translator->trans('lastname')
            )
        )
        ->add('email', TextType::class, array(
                'label' => $this->translator->trans('email')
            )
        )
        ->add('password', PasswordType::class, array(
                'label' => $this->translator->trans('password'),
                'required' => empty($builder->getData()->getId()),
                'empty_data' => ''
            )
        )
        ->add('roles', ChoiceType::class, [
                'required' => false,
                'multiple' => true,
                'expanded' => true,
                'choices' => User::ROLES,
                'empty_data' => 'ROLE_USER',
            ]
        )
        ->add('photo', FileType::class, [
            'label' => 'Photo',

            // unmapped means that this field is not associated to any entity property
            'mapped' => false,

            // make it optional so you don't have to re-upload the PDF file
            // every time you edit the Product details
            'required' => false,

            // unmapped fields can't define their validation using annotations
            // in the associated entity, so you can use the PHP constraint classes
            'constraints' => [
                new File([
                    'maxSize' => '1024k',
                    'mimeTypes' => [
                        'image/jpg',
                        'image/png',
                        'image/jpeg',
                    ],
                    'mimeTypesMessage' => 'Please upload a valid image',
                ])
            ],
        ])

        ->add('save', SubmitType::class, ['label' => $this->editing($builder) ? 'Change user' : 'Add user']);
    }

    public function editing($builder) {
        if (!empty($builder->getData()->getId())) {
            return true;
        }

        return false;
    }
}