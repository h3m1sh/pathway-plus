<?php

namespace App\Form;

use App\Entity\MicroCredential;
use App\Entity\Skill;
use App\Repository\MicroCredentialRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MicroCredentialForm extends AbstractType
{
    public function __construct(private MicroCredentialRepository $microCredentialRepository)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Get dynamic categories from existing micro-credentials
        $categories = $this->microCredentialRepository->findDistinctCategories();

        $categoryChoices = [];
        foreach ($categories as $category) {
            if (!empty($category)) {
                $categoryChoices[$category] = $category;
            }
        }

        
        $levelChoices = [
            'Beginner' => 'Beginner',
            'Intermediate' => 'Intermediate',
            'Advanced' => 'Advanced',
            'Expert' => 'Expert',
            'Foundation' => 'Foundation',
            'Professional' => 'Professional'
        ];

        $builder
            ->add('name', TextType::class, [
                'label' => 'Micro-Credential Name',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter micro-credential name '
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => '4',
                    'placeholder' => 'Provide a detailed description of this micro-credential, including learning outcomes and key competencies...'
                ]
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'Category',
                'choices' => $categoryChoices,
                'placeholder' => 'Select a category...',
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            ->add('level', ChoiceType::class, [
                'label' => 'Micro-Credential Level',
                'choices' => $levelChoices,
                'placeholder' => 'Select level...',
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            ->add('badgeFile', FileType::class, [
                'label' => 'Badge Icon Upload',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/png',
                            'image/jpeg',
                            'image/jpg',
                            'image/svg+xml',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image file (PNG, JPG, SVG)',
                    ])
                ],
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'image/*'
                ]
            ])
            ->add('badgeUrl', UrlType::class, [
                'label' => 'Badge URL',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'https://example.com/badges/credential-badge.png'
                ]
            ])
            ->add('isVisible', CheckboxType::class, [
                'label' => 'Make this micro-credential visible to students',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ],
                'label_attr' => [
                    'class' => 'form-check-label'
                ]
            ])
            ->add('skills', EntityType::class, [
                'class' => Skill::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => false,
                'required' => false,
                'label' => 'Associated Skills',
                'attr' => [
                    'class' => 'form-select',
                    'size' => '5'
                ],
                'help' => 'Select skills that are related to this micro-credential (hold Ctrl/Cmd to select multiple)'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MicroCredential::class,
        ]);
    }
}
