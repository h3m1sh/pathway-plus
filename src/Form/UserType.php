<?php

namespace App\Form;

use App\Entity\Skill;
use App\Entity\MicroCredential;
use App\Entity\JobRole;
use App\Repository\SkillRepository;
use App\Repository\MicroCredentialRepository;
use App\Repository\JobRoleRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName')
            ->add('lastName')
            ->add('email')
            ->add('studentId', null, [
                'required' => false,
                'label' => 'Student ID',
                'attr' => [
                    'placeholder' => 'Optional - for student accounts only'
                ]
            ])
            ->add('avatarUrl', null, [
                'required' => false,
                'label' => 'Avatar URL',
                'attr' => [
                    'placeholder' => 'https://example.com/avatar.jpg'
                ]
            ])
            ->add('roles', ChoiceType::class, [
                'choices' => [
                    'Student' => 'ROLE_STUDENT',
                    'Admin' => 'ROLE_ADMIN',
                ],
                'expanded' => true,
                'multiple' => true,
                'required' => true,
            ])
            ->add('isActive', CheckboxType::class, [
                'required' => false,
                'label' => 'Active Account',
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => ['label' => 'New Password'],
                'second_options' => ['label' => 'Repeat Password'],
                'required' => false,
            ])
            ->add('skills', EntityType::class, [
                'class' => Skill::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => false,
                'required' => false,
                'label' => 'Skills',
                'attr' => [
                    'class' => 'form-select',
                    'data-placeholder' => 'Select skills...'
                ],
                'query_builder' => function (SkillRepository $sr) {
                    return $sr->createQueryBuilder('s')
                        ->orderBy('s.name', 'ASC');
                }
            ])
            ->add('microCredentials', EntityType::class, [
                'class' => MicroCredential::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => false,
                'required' => false,
                'label' => 'Micro-Credentials',
                'attr' => [
                    'class' => 'form-select',
                    'data-placeholder' => 'Select micro-credentials...'
                ],
                'query_builder' => function (MicroCredentialRepository $mcr) {
                    return $mcr->createQueryBuilder('mc')
                        ->orderBy('mc.name', 'ASC');
                }
            ])
            ->add('jobRoles', EntityType::class, [
                'class' => JobRole::class,
                'choice_label' => 'title',
                'multiple' => true,
                'expanded' => false,
                'required' => false,
                'label' => 'Career Interests',
                'attr' => [
                    'class' => 'form-select',
                    'data-placeholder' => 'Select job roles...'
                ],
                'query_builder' => function (JobRoleRepository $jrr) {
                    return $jrr->createQueryBuilder('jr')
                        ->where('jr.isArchived = false')
                        ->orderBy('jr.title', 'ASC');
                }
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
} 