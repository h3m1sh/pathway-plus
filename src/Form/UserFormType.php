<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\Skill;
use App\Entity\MicroCredential;
use App\Entity\JobRole;
use App\Repository\SkillRepository;
use App\Repository\MicroCredentialRepository;
use App\Repository\JobRoleRepository;

class UserFormType extends AbstractType
{
    public function __construct(
        private SkillRepository $skillRepository,
        private MicroCredentialRepository $microCredentialRepository,
        private JobRoleRepository $jobRoleRepository
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email Address',
                'required' => true,
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'User Roles',
                'choices' => [
                    'Student' => 'ROLE_STUDENT',
                    'Admin' => 'ROLE_ADMIN',
                ],
                'multiple' => true,
                'expanded' => true,
                'required' => false,
            ])
            ->add('firstName', TextType::class, [
                'label' => 'First Name',
                'required' => true,
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Last Name',
                'required' => true,
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Active User',
                'required' => false,
            ])
            ->add('studentId', TextType::class, [
                'label' => 'Student ID',
                'required' => false,
            ])
            ->add('avatarUrl', UrlType::class, [
                'label' => 'Avatar URL',
                'required' => false,
                'default_protocol' => 'https',
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
            ->add('jobRoleInterests', EntityType::class, [
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
            ])
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $user = $event->getData();
            $form = $event->getForm();
            
            $isNew = $user === null || $user->getId() === null;
            
            $form->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'required' => $isNew,
                'first_options' => ['label' => $isNew ? 'Password' : 'New Password'],
                'second_options' => ['label' => $isNew ? 'Confirm Password' : 'Confirm New Password'],
                'invalid_message' => 'Passwords must match.',
            ]);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
