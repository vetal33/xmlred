<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email', EmailType::class, [
                'attr' => ['class' => 'form-control', 'placeholder' => 'Email'],
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'label' => 'Погоджуюсь з умовами використання',
                'constraints' => [
                    new IsTrue([
                        'message' => 'You should agree to our terms.',
                    ]),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Password'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a password',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Your password should be at least {{ limit }} characters',
                        // max length allowed by Symfony for security reasons
                        'max' => 4096,
                    ]),
                ],

            ]);
        /* ->add('plainPassword', RepeatedType::class, [
             'type' => PasswordType::class,
             'first_options' => [
                 'mapped' => false,
                 'attr' => ['class' => 'form-control', 'placeholder' => 'Password'],
                 'constraints' => [
                     new NotBlank([
                         'message' => 'Please enter a password',
                     ]),
                     new Length([
                         'min' => 6,
                         'minMessage' => 'Your password should be at least {{ limit }} characters',
                         // max length allowed by Symfony for security reasons
                         'max' => 4096,
                     ]),
                 ],
             ],
             'second_options' => [
                 'mapped' => false,
                 'attr' => ['class' => 'form-control', 'placeholder' => 'Repeat password'],
                 'constraints' => [
                     new NotBlank([
                         'message' => 'Please enter a password',
                     ]),
                     new Length([
                         'min' => 6,
                         'minMessage' => 'Your password should be at least {{ limit }} characters',
                         // max length allowed by Symfony for security reasons
                         'max' => 4096,
                     ]),
                 ],
             ],

             // instead of being set onto the object directly,
             // this is read and encoded in the controller


         ]);*/
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
