<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\JobRole;
use App\Entity\Skill;
use App\Repository\JobRoleRepository;
use App\Repository\SkillRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class JobRoleFormType extends AbstractType
{
    public function __construct(
        private JobRoleRepository $jobRoleRepository,
        private SkillRepository $skillRepository
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $industries = $this->jobRoleRepository->findDistinctIndustries();
        $salaryRanges = $this->jobRoleRepository->findDistinctSalaryRanges();

        $industryChoices = [];
        foreach ($industries as $industry) {
            if (!empty($industry)) {
                $industryChoices[$industry] = $industry;
            }
        }

        $salaryChoices = [];
        foreach ($salaryRanges as $salary) {
            if (!empty($salary)) {
                $salaryChoices[$salary] = $salary;
            }
        }

        $builder
            ->add('title', TextType::class, [
                'label' => 'Job Title',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'e.g. Software Developer'
                ]
            ])
            ->add('jobCode', TextType::class, [
                'label' => 'Job Code',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'e.g. J80312'
                ]
            ])
            ->add('anzsco', TextType::class, [
                'label' => 'ANZSCO Code',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'e.g. 261313'
                ]
            ])
            ->add('industry', ChoiceType::class, [
                'label' => 'Industry',
                'choices' => $industryChoices,
                'placeholder' => 'Select an industry...',
                'required' => false,
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('salaryRange', ChoiceType::class, [
                'label' => 'Salary Range',
                'choices' => $salaryChoices,
                'placeholder' => 'Select a salary range...',
                'required' => false,
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('yearsOfTraining', TextType::class, [
                'label' => 'Years of Training',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'e.g. 3-4 years'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Job Description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Describe the job role, responsibilities, and requirements...'
                ]
            ])
            ->add('entryRequirements', TextareaType::class, [
                'label' => 'Entry Requirements',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'List the qualifications and experience needed...'
                ]
            ])
            ->add('jobOpportunities', TextareaType::class, [
                'label' => 'Job Opportunities',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Describe the career opportunities and progression...'
                ]
            ])
            ->add('jobOpportunitiesCaption', TextareaType::class, [
                'label' => 'Job Opportunities Caption',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 2,
                    'placeholder' => 'Brief summary of opportunities...'
                ]
            ])
            ->add('skills', EntityType::class, [
                'class' => Skill::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => false,
                'required' => false,
                'label' => 'Required Skills',
                'attr' => [
                    'class' => 'form-control',
                    'data-placeholder' => 'Select skills...'
                ],
                'query_builder' => function () {
                    return $this->skillRepository->createQueryBuilder('s')
                        ->orderBy('s.name', 'ASC');
                }
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => JobRole::class,
        ]);
    }
} 