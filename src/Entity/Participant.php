<?php

namespace App\Entity;

use App\Repository\ParticipantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ParticipantRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_PSEUDO', fields: ['pseudo'])]
#[UniqueEntity(fields: ['mail'], message: 'Un utilisateur est déjà connu à cette adresse mail.')]
#[UniqueEntity(fields: ['pseudo'], message: 'Un utilisateur est déjà connu avec ce pseudo.')]
class Participant implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank(message: 'Le pseudo est obligatoire.')]
    #[Assert\NotNull(message: 'Le pseudo est obligatoire.')]
    #[Assert\Length(min: 2, max: 180, minMessage: 'Le pseudo doit contenir au moins 2 caractères.', maxMessage: 'Le pseudo ne peut dépasser les 180 caractères')]
    #[ORM\Column(length: 180)]
    private ?string $pseudo = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    #[Assert\NotNull(message: 'Le nom est obligatoire.')]
    #[Assert\Length(min: 2, max: 255, minMessage: 'Le nom doit contenir au moins 2 caractères.', maxMessage: 'Le nom ne peut dépasser les 255 caractères')]
    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[Assert\NotBlank(message: 'Le prénom est obligatoire.')]
    #[Assert\NotNull(message: 'Le prénom est obligatoire.')]
    #[Assert\Length(min: 2, max: 255, minMessage: 'Le prénom doit contenir au moins 2 caractères.', maxMessage: 'Le prénom ne peut dépasser les 255 caractères')]
    #[ORM\Column(length: 255)]
    private ?string $prenom = null;

    #[Assert\NotBlank(message: 'Le téléphone est obligatoire.')]
    #[Assert\NotNull(message: 'Le téléphone est obligatoire.')]
    #[Assert\Length(min: 9, max: 255, minMessage: 'Le téléphone doit contenir au moins 9 chiffres.', maxMessage: 'Le téléphone ne peut dépasser les 255 caractères')]
    #[ORM\Column(length: 255)]
    private ?string $telephone = null;

    #[Assert\Email(message: 'L\'adresse mail est invalide.')]
    #[Assert\Length(min: 2, max: 255, minMessage: 'l\'addresse mail doit contenir au moins 2 charactères.', maxMessage: 'l\'addresse mail ne peut dépasser les 255 caractères')]
    #[ORM\Column(length: 255)]
    private ?string $mail = null;

    #[ORM\Column]
    private ?bool $actif = true;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nomFichierPhoto = null;

    #[ORM\ManyToOne(inversedBy: 'affiliates')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Campus $campus = null;

    /**
     * @var Collection<int, Sortie>
     */
    #[ORM\OneToMany(targetEntity: Sortie::class, mappedBy: 'organisateur')]
    private Collection $sortiesOrganisateur;

    /**
     * @var Collection<int, Sortie>
     */
    #[ORM\ManyToMany(targetEntity: Sortie::class, mappedBy: 'inscrits')]
    private Collection $sortiesInscrits;

    public function __construct()
    {
        $this->roles = ['ROLE_PARTICIPANT'];
        $this->sortiesOrganisateur = new ArrayCollection();
        $this->sortiesInscrits = new ArrayCollection();
    }

    public function __toString(): string
    {
        return "$this->nom $this->prenom";
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPseudo(): ?string
    {
        return $this->pseudo;
    }

    public function setPseudo(string $pseudo): static
    {
        $this->pseudo = $pseudo;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string)$this->pseudo;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array)$this;
        $data["\0" . self::class . "\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(string $telephone): static
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getMail(): ?string
    {
        return $this->mail;
    }

    public function setMail(string $mail): static
    {
        $this->mail = $mail;

        return $this;
    }

    public function isActif(): ?bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;

        return $this;
    }

    public function getNomFichierPhoto(): ?string
    {
        return $this->nomFichierPhoto;
    }

    public function setNomFichierPhoto(?string $nomFichierPhoto): static
    {
        $this->nomFichierPhoto = $nomFichierPhoto;

        return $this;
    }

    public function getCampus(): ?Campus
    {
        return $this->campus;
    }

    public function setCampus(?Campus $campus): static
    {
        $this->campus = $campus;

        return $this;
    }

    /**
     * @return Collection<int, Sortie>
     */
    public function getSortiesOrganisateur(): Collection
    {
        return $this->sortiesOrganisateur;
    }

    public function addSortiesOrganisateur(Sortie $sortiesOrganisateur): static
    {
        if (!$this->sortiesOrganisateur->contains($sortiesOrganisateur)) {
            $this->sortiesOrganisateur->add($sortiesOrganisateur);
            $sortiesOrganisateur->setOrganisateur($this);
        }

        return $this;
    }

    public function removeSortiesOrganisateur(Sortie $sortiesOrganisateur): static
    {
        if ($this->sortiesOrganisateur->removeElement($sortiesOrganisateur)) {
            // set the owning side to null (unless already changed)
            if ($sortiesOrganisateur->getOrganisateur() === $this) {
                $sortiesOrganisateur->setOrganisateur(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Sortie>
     */
    public function getSortiesInscrits(): Collection
    {
        return $this->sortiesInscrits;
    }

    public function addSortiesInscrit(Sortie $sortiesInscrit): static
    {
        if (!$this->sortiesInscrits->contains($sortiesInscrit)) {
            $this->sortiesInscrits->add($sortiesInscrit);
            $sortiesInscrit->addInscrit($this);
        }

        return $this;
    }

    public function removeSortiesInscrit(Sortie $sortiesInscrit): static
    {
        if ($this->sortiesInscrits->removeElement($sortiesInscrit)) {
            $sortiesInscrit->removeInscrit($this);
        }

        return $this;
    }

}
