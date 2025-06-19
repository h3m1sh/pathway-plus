<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ProfileFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isAdmin = $options['is_admin'] ?? false;
        
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'First Name',
                'required' => true,
                'disabled' => !$isAdmin, // Disable for non-admins
                'constraints' => [
                    new Assert\NotBlank(['message' => 'First name is required']),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 100,
                        'minMessage' => 'First name must be at least {{ limit }} characters long',
                        'maxMessage' => 'First name cannot be longer than {{ limit }} characters'
                    ])
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter your first name'
                ]
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Last Name',
                'required' => true,
                'disabled' => !$isAdmin, // Disable for non-admins
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Last name is required']),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 100,
                        'minMessage' => 'Last name must be at least {{ limit }} characters long',
                        'maxMessage' => 'Last name cannot be longer than {{ limit }} characters'
                    ])
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter your last name'
                ]
            ])
            ->add('studentId', TextType::class, [
                'label' => 'Student Number',
                'required' => false,
                'disabled' => !$isAdmin, // Disable for non-admins
                'constraints' => [
                    new Assert\Length([
                        'max' => 50,
                        'maxMessage' => 'Student number cannot be longer than {{ limit }} characters'
                    ])
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter your student number (optional)'
                ]
            ])
            ->add('avatarFile', FileType::class, [
                'label' => 'Profile Picture',
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new Assert\File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif'
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image file (JPEG, PNG, or GIF)',
                        'maxSizeMessage' => 'The file is too large. Maximum size is {{ limit }} {{ suffix }}'
                    ])
                ],
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'image/*'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_admin' => false,
        ]);
    }
} 