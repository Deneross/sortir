<?php

namespace App\Util;

use App\Entity\Participant;
use App\Exception\ParticipantNotFound;
use App\Repository\ParticipantRepository;
use Doctrine\ORM\EntityNotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;

class ImgManager
{
    public function __construct(
        private readonly ContainerBagInterface $container,
        private readonly SluggerInterface      $slug)
    {
    }


    public function getProfilPicture(Participant $p): ?string
    {
        $nomImg = $p->getNomFichierPhoto();
        return $nomImg ? ($this->container->get('app.public_images_directory') . '/' . $nomImg) : '/images/default.png';
    }

    public function updateImg(Participant $participant, FormInterface $form): Participant
    {
        //Suppression de l'img si demandée
        if ($form->has('deleteImage') && $form->get('deleteImage')->getData()) {
            $this->suppImg($participant);
        }

        //Ajout de l'img si soumise
        $imgDuFom = $form->get('image')->getData();

        if ($imgDuFom) {

            //Au cas où c'est un remplacement d'image déjà existante
            if($participant->getNomFichierPhoto()){
                $this->suppImg($participant);
            }

            $setName = pathinfo($participant->getPseudo(), PATHINFO_FILENAME);
            $safeFileName = $this->slug->slug($setName);
            $newFileName = $safeFileName . '-' . uniqid() . '.' . $imgDuFom->guessExtension();

            try {
                $imgDuFom->move($this->container->get('app.project_images_directory'), $newFileName);
            } catch (FileException $e) {
                throw new FileException($e->getMessage());
            } catch (NotFoundExceptionInterface|ContainerExceptionInterface $e) {
            }

            $participant->setNomFichierPhoto($newFileName);
        }
        return $participant;
    }

    private function suppImg(Participant $participant): void{
        unlink($this->container->get('app.project_images_directory') . '/' . $participant->getNomFichierPhoto());
        $participant->setNomFichierPhoto(null);
    }
}
