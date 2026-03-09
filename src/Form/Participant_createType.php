<?php

namespace App\Form;

use App\Entity\Campus;
use App\Entity\Participant;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class Participant_createType extends AbstractType
{
    private const START_ID = 'participant';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('campus', EntityType::class, [
                'class' => Campus::class,
                'choice_label' => 'name',
                'placeholder' => 'Choisir un campus',
                'label' => 'Campus',
                'attr' => [
                    'id' => self::START_ID . 'Campus',
                ],
                'label_attr' => [
                    'id' => self::START_ID . 'CampusLabel',
                ],
            ])

            ->add('pseudo', TextType::class, [
                'attr' => [
                    'id' => self::START_ID . 'Pseudo',
                    'placeholder' => 'Indiquez un pseudo',
                ],
                'label' => 'Pseudo',
                'label_attr' => [
                    'id' => self::START_ID . 'PseudoLabel',
                ],
            ])

            ->add('prenom', TextType::class, [
                'attr' => [
                    'id' => self::START_ID . 'Prenom',
                    'placeholder' => 'Indiquez le prénom',
                ],
                'label' => 'Prénom',
                'label_attr' => [
                    'id' => self::START_ID . 'PrenomLabel',
                ],
            ])

            ->add('nom', TextType::class, [
                'attr' => [
                    'id' => self::START_ID . 'Nom',
                    'placeholder' => 'Indiquez le nom',
                ],
                'label' => 'Nom',
                'label_attr' => [
                    'id' => self::START_ID . 'NomLabel',
                ],
            ])

            ->add('telephone', TelType::class, [
                'required' => false,
                'attr' => [
                    'id' => self::START_ID . 'Telephone',
                    'placeholder' => '06...',
                ],
                'label' => 'Téléphone',
                'label_attr' => [
                    'id' => self::START_ID . 'TelephoneLabel',
                ],
            ])

            ->add('mail', EmailType::class, [
                'attr' => [
                    'id' => self::START_ID . 'AdresseMail',
                    'placeholder' => 'exemple@email.com',
                ],
                'label' => 'Adresse mail',
                'label_attr' => [
                    'id' => self::START_ID . 'AdresseMailLabel',
                ],
            ])

            ->add('newPassword', RepeatedType::class, [
                'mapped' => false,
                'required' => true,
                'type' => PasswordType::class,
                'invalid_message' => 'Les mots de passe ne correspondent pas.',
                'first_options' => [
                    'attr' => [
                        'id' => self::START_ID . 'MotDePasse',
                        'placeholder' => 'Indiquez votre mot de passe',
                    ],
                    'label' => 'Mot de passe',
                    'label_attr' => [
                        'id' => self::START_ID . 'MotDePasse',
                    ],
                    'help' => 'Attention, au moins 8 caractères dont un chiffre, une majuscule, une minuscle et un caractère spécial sont attenuds',
                    'constraints' => [
                        new Regex(pattern: '/(?=.*\d)(?=.*[A-Z])(?=.*[a-z])(?=.*[^\w\d\s:])([^\s]){8}/',
                            message: 'Le mot de passe doit contenir au moins 8 caractères dont
                            un chiffre, une lettre majuscule, une lettre miniscule, un caractère spécial.')
                    ]
                ],
                'second_options' => [
                    'mapped' => false,
                    'attr' => [
                        'id' => self::START_ID . 'PasswordConfirmation',
                        'placeholder' => 'Re-saisissez le même mot de passe',
                    ],
                    'label' => 'Confirmation du mot de passe',
                    'label_attr' => [
                        'id' => self::START_ID . 'PasswordConfirmation',
                    ],
                ],
            ])

            //Les champs liés aux images
            ->add('image', FileType::class, [
                'label' => 'Ma photo',
                'required' => false,
                'mapped' => false,
                'help' => 'Taille maximum 1M. Seuls les formats .jpeg, .jpg, ou .png sont autorisés.',
                'constraints' => [
                    new File(
                        maxSize: '1024k',
                        maxSizeMessage: 'Fichier trop volumineux dépassant les 1M aurosiés',
                        extensions: ['jpg', 'jpeg', 'png'],
                        extensionsMessage: 'Mauvais format d\'image importé. Veuillez transmettre un fichier de format jpeg, jpg ou png' ,
                    )
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Participant::class,
        ]);
    }
}
