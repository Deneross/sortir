<?php

namespace App\Form;

use App\Entity\Campus;
use App\Entity\Etat;
use App\Entity\Lieu;
use App\Entity\Participant;
use App\Entity\Sortie;
use App\Entity\Ville;
use App\Repository\VilleRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SortieType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $campus = $options['CampusToUseAsFilter'];
        $update = $options['update'];
        $infoVille = $options['dataUrlVille'];
        $cpVille  = $options['cpVilleOrigine'];

        /**
         * Attention ce formulaire a de la logique qui dépend du controller Sortie:
         * Aucun champ du lieu n'est mapped, le mapping se fait dans le service LieuManager
         * Une query est fait sur le code postal pour filter par le campus
         */
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
                'label' => 'Nombre de places',
                'attr' => [
                    'min' => 1,
                    'placeholder' => 1,
                ]
            ])
            ->add('duree', NumberType::class, [
                'label' => 'Durée (en minutes)',
                'attr' => [
                    'min' => 60,
                    'placeholder' => 60,
                ]
            ])
            ->add('infosSortie', TextareaType::class, [
                'label' => 'Description et infos',
                'attr' => [
                    'placeholder' => 'Donnez plus d\'information sur votre sortie',
                    'rows' => 5
                ]
            ])
            ->add('campus', EntityType::class, [
                'class' => Campus::class,
                'choice_label' => 'name',
                'attr' => [
                    'disabled' => true,
                ]
            ])
            ->add('lieuVille', EntityType::class, [
                'label' => 'Ville',
                'class' => Ville::class,
                'choice_label' => 'name',
                'multiple' => false,
                'mapped' => false,
                'query_builder' => function (VilleRepository $repo) use ($campus) {
                    return $repo->createQueryBuilder('v')
                        ->where('v.campus = :campus')
                        ->setParameter('campus', $campus);
                },
                'attr'=>[
                    'data-url' => $infoVille
                ]
            ])
            ->add('lieuNom', TextType::class, [
                'label' => 'Lieu(x)',
                'attr' => [
                    'placeholder' => 'Indiquez où vous souhaitez organiser cette sortie',
                ],
                'mapped' => false,
            ])
            ->add('lieuCP', TextType::class, [
                'label' => 'Code postal',
                'attr' => [
                    'disabled' => true,
                    'value' => $cpVille? $cpVille : "Autocomplétion selon le choix de ville"
                ],
                'mapped' => false,
                'required' => false,
            ])
            ->add('enregistrer', SubmitType::class, [
                'label' => 'Enregistrer',
                'attr' => [
                    'class' => 'btn btn-info text-white',
                ]
            ])
            ->add('publier', SubmitType::class, [
                'label' => 'Publier',
                'attr' => [
                    'class' => 'btn btn-primary',
                    'onclick' => "return confirm('Une fois la sortie publiée, vous ne pourrez plus la modifier');"
                ]
            ]);
        if ($update) {
            $builder->add('supprimer', SubmitType::class, [
                'label' => 'Supprimer',
                'attr' => [
                    'class' => 'btn btn-danger',
                    'onclick' => "return confirm('Cette action est irreversible, il ne sera pas possible de restaurer la sortie.');"
                ]
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Sortie::class,
            'CampusToUseAsFilter' => null,
            'update' => false,
            'dataUrlVille' => null,
            'cpVilleOrigine' => null
        ]);
    }
}
