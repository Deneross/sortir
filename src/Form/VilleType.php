<?php

namespace App\Form;

use App\Entity\Campus;
use App\Entity\Ville;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VilleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('campus', EntityType::class, [
                'label_attr' => [
                    'hidden' => true,
                ],
                'attr' => [
                    'class' => 'mt-5'
                ],
                'class' => Campus::class,
                'choice_label' => 'name',
                'help' => '/!\ le choix du lieu d\'une sortie est filtré sur le campus.
                Créez une ville qui fasse partie de la même zone géographique que ce choix.',
            ])
            ->add('name', TextType::class, [
                'label_attr' => [
                    'hidden' => true,
                ]
            ])
            ->add('codePostal', TextType::class, [
                'label_attr' => [
                    'hidden' => true,
                ]
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Enregistrer',
                'attr' => [
                    'class' => 'btn btn-success',
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ville::class,
            'csrf_protection' => false,
        ]);
    }
}
