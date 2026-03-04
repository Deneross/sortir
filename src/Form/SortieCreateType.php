<?php

namespace App\Form;

use App\Entity\Campus;
use App\Entity\Etat;
use App\Entity\Lieu;
use App\Entity\Participant;
use App\Entity\Sortie;
use App\Entity\Ville;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SortieCreateType extends AbstractType
{
    /**
     * Format spécial car à la création, l'entité Lieu n'est pas lié à la sortie
     * On crée à partir du controleur le lieu qui sera référé à cette sortie
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de la sortie',
                'attr' => [
                    'placeholder' => 'Indiquez un nom pour cette sortie proposée',
                ]
            ])
            ->add('dateHeureDebut', DateTimeType::class, [
                'label' => 'Date et heure de la sortie',
                'attr' => [
                    'min' => new \DateTime('+1 hour')->format('Y-m-d\TH:i'),
                ],
                'widget' => 'single_text',
            ])
            ->add('dateLimiteInscription', DateTimeType::class, [
                'label' => 'Date limite d\'inscription',
                'attr' => [
                    'min' => new \DateTime('+1 hour')->format('Y-m-d\TH:i'),
                ],
                'widget' => 'single_text',
            ])
            ->add('nbInscriptionMax', NumberType::class, [
                'label'=>'Nombre de places',
                'attr'=>[
                    'min'=>1,
                    'placeholder'=>1,
                ]
        ])
            ->add('duree', NumberType::class, [
                'label' => 'Durée (en minutes)',
                'attr'=>[
                    'min'=>60,
                    'placeholder'=>60,
                ]
            ])
            ->add('infosSortie', TextareaType::class, [
                'label' => 'Description et infos',
                'attr'=>[
                    'placeholder' => 'Donnez plus d\'information sur votre sortie'
                ]
            ])
            ->add('campus', EntityType::class, [
                'class' => Campus::class,
                'choice_label' => 'name',
            ])
            ->add('lieuNom', TextType::class, [
                'label' => 'Lieu',
                'attr'=>[
                    'placeholder' => 'Indiquez où vous souhaitez organiser cette sortie',
                ],
                'mapped' => false,
            ])
            ->add('lieuRue', TextType::class, [
                'label' => 'Rue',
                'attr'=>[
                    'placeholder' => 'Indiquez l\'adresse correspondante',
                ],
                'mapped' => false,
            ])
            ->add('lieuCodePostal', EntityType::class, [
                'class' => Ville::class,
                'choice_label' => 'codePostal',
                'multiple' => false,
                'mapped' => false,
            ])
            ->add('lieuCoordonnees', TextType::class, [
                'label' => 'Latitude / Longitude',
                'attr'=>[
                    'placeholder' => '41.40338, 2.17403',
                ],
                'mapped' => false,
                'required' => false,
                'help' => 'Si vous le souhaitez, faciliter vos retrouvaille en indiquant les coordonées GPS du lieu de rencontre',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Sortie::class,
        ]);
    }
}
