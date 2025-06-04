<?php

namespace App\Form;

use App\Entity\Skill;
use App\Repository\SkillRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class SkillFormType extends AbstractType
{

    public function __construct(private SkillRepository $skillRepository)
    {

    }


    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $categories = $this->skillRepository->findDistinctCategories();


        $categoryChoices = [];
        foreach ($categories as $category) {
            if (!empty($category)) {
                $categoryChoices[$category] = $category;
            }
        }

        $builder
            ->add('name', TextType::class, [
                'label' => 'Skill Name',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'Skill Category',
                'choices' => $categoryChoices,
                'placeholder' => 'Select a category...',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('difficulty', ChoiceType::class, [
                'label' => 'Skill Difficulty',
                'choices' => [
                    'Beginner' => 'Beginner',
                    'Intermediate' => 'Intermediate',
                    'Advanced' => 'Advanced',
                ],
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Skill Description',
                'attr' => [
                    'class' => 'form-control'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Skill::class,
        ]);
    }
}
