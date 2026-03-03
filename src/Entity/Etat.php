<?php

namespace App\Entity;

use App\Repository\EtatRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EtatRepository::class)]
#[ORM\Table(name: 'etat')]
class Etat
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    private ?int $id;

    #[ORM\Column(length: 20)]
    private ?string $libelle = null;

    /**
     * @var Collection<int, Sortie>
     */
    #[ORM\OneToMany(targetEntity: Sortie::class, mappedBy: 'etat')]
    private Collection $sortiesEtat;

    public function __construct()
    {
        $this->sortiesEtat = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id; return $this;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;

        return $this;
    }

    /**
     * @return Collection<int, Sortie>
     */
    public function getSortiesEtat(): Collection
    {
        return $this->sortiesEtat;
    }

    public function addSortiesEtat(Sortie $sortiesEtat): static
    {
        if (!$this->sortiesEtat->contains($sortiesEtat)) {
            $this->sortiesEtat->add($sortiesEtat);
            $sortiesEtat->setEtat($this);
        }

        return $this;
    }

    public function removeSortiesEtat(Sortie $sortiesEtat): static
    {
        if ($this->sortiesEtat->removeElement($sortiesEtat)) {
            // set the owning side to null (unless already changed)
            if ($sortiesEtat->getEtat() === $this) {
                $sortiesEtat->setEtat(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->libelle;
    }
}
