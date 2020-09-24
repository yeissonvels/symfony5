<?php
namespace App\Form\Type;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class  UserType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // Data Model $builder->getData()
        // print_r($builder->getData());
        $builder
        ->add('firstname', TextType::class)
        ->add('lastname', TextType::class)
        ->add('email', TextType::class)
        ->add('password', PasswordType::class, array(
                'label' => 'Password',
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
        ->add('save', SubmitType::class, ['label' => $this->editing($builder) ? 'Change user' : 'Add user']);
    }

    public function editing($builder) {
        if (!empty($builder->getData()->getId())) {
            return true;
        }

        return false;
    }
}