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

    public function __construct()
    {
        $this->affiliates = new ArrayCollection();
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
}
