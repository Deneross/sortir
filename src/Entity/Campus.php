<?php

namespace App\Entity;

use App\Repository\CampusRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: CampusRepository::class)]
#[UniqueEntity(fields: ['name'], message: 'Ce campus existe déjà')]
class Campus
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $name = null;

    /**
     * @var Collection<int, Participant>
     */
    #[ORM\OneToMany(targetEntity: Participant::class, mappedBy: 'campus')]
    private Collection $affiliates;

    /**
     * @var Collection<int, Sortie>
     */
    #[ORM\OneToMany(targetEntity: Sortie::class, mappedBy: 'campus')]
    private Collection $sorties;

    /**
     * @var Collection<int, Ville>
     */
    #[ORM\OneToMany(targetEntity: Ville::class, mappedBy: 'Campus', orphanRemoval: true)]
    private Collection $villesDeSortie;

    public function __construct()
    {
        $this->affiliates = new ArrayCollection();
        $this->sorties = new ArrayCollection();
        $this->villesDeSortie = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, Participant>
     */
    public function getAffiliates(): Collection
    {
        return $this->affiliates;
    }

    public function addAffiliate(Participant $affiliate): static
    {
        if (!$this->affiliates->contains($affiliate)) {
            $this->affiliates->add($affiliate);
            $affiliate->setCampus($this);
        }

        return $this;
    }

    public function removeAffiliate(Participant $affiliate): static
    {
        if ($this->affiliates->removeElement($affiliate)) {
            // set the owning side to null (unless already changed)
            if ($affiliate->getCampus() === $this) {
                $affiliate->setCampus(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Sortie>
     */
    public function getSorties(): Collection
    {
        return $this->sorties;
    }

    public function addSortie(Sortie $sortie): static
    {
        if (!$this->sorties->contains($sortie)) {
            $this->sorties->add($sortie);
            $sortie->setCampus($this);
        }

        return $this;
    }

    public function removeSortie(Sortie $sortie): static
    {
        if ($this->sorties->removeElement($sortie)) {
            // set the owning side to null (unless already changed)
            if ($sortie->getCampus() === $this) {
                $sortie->setCampus(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->getName();
    }

    /**
     * @return Collection<int, Ville>
     */
    public function getVillesDeSortie(): Collection
    {
        return $this->villesDeSortie;
    }

    public function addVillesDeSortie(Ville $villesDeSortie): static
    {
        if (!$this->villesDeSortie->contains($villesDeSortie)) {
            $this->villesDeSortie->add($villesDeSortie);
            $villesDeSortie->setCampus($this);
        }

        return $this;
    }

    public function removeVillesDeSortie(Ville $villesDeSortie): static
    {
        if ($this->villesDeSortie->removeElement($villesDeSortie)) {
            // set the owning side to null (unless already changed)
            if ($villesDeSortie->getCampus() === $this) {
                $villesDeSortie->setCampus(null);
            }
        }

        return $this;
    }
}
