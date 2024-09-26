<?php

namespace App\Entity;

use App\Repository\ParticipantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ParticipantRepository::class)]
class Participant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $name = null;

    #[Assert\Email(message: "L'adresse email '{{ value }}' n'est pas une adresse valide.")]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\ManyToMany(targetEntity: Participant::class, inversedBy: 'excludedBy')]
    private Collection $exclusions;

    /**
     * @var Collection<int, Draw>
     */
    #[ORM\OneToMany(mappedBy: 'giver', targetEntity: Draw::class)]
    private Collection $givenDraws;

    #[ORM\OneToMany(mappedBy: 'receiver', targetEntity: Draw::class)]
    private Collection $receivedDraws;

    #[ORM\ManyToOne(inversedBy: 'participants')]
    private ?Group $group = null;

    public function __construct()
    {
        $this->givenDraws = new ArrayCollection();
        $this->receivedDraws = new ArrayCollection();
        $this->exclusions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return Collection<int, Participant>
     */
    public function getExclusions(): Collection
    {
        return $this->exclusions;
    }

    public function addExclusion(Participant $participant): self
    {
        if (!$this->exclusions->contains($participant)) {
            $this->exclusions->add($participant);
        }

        return $this;
    }

    public function removeExclusion(Participant $participant): self
    {
        $this->exclusions->removeElement($participant);

        return $this;
    }

    public function getGivenDraws(): Collection
    {
        return $this->givenDraws;
    }

    public function addGivenDraw(Draw $draw): self
    {
        if (!$this->givenDraws->contains($draw)) {
            $this->givenDraws->add($draw);
            $draw->setGiver($this);
        }

        return $this;
    }

    public function removeGivenDraw(Draw $draw): self
    {
        if ($this->givenDraws->removeElement($draw)) {
            if ($draw->getGiver() === $this) {
                $draw->setGiver(null);
            }
        }

        return $this;
    }

    public function getReceivedDraws(): Collection
    {
        return $this->receivedDraws;
    }

    public function addReceivedDraw(Draw $draw): self
    {
        if (!$this->receivedDraws->contains($draw)) {
            $this->receivedDraws->add($draw);
            $draw->setReceiver($this);
        }

        return $this;
    }

    public function removeReceivedDraw(Draw $draw): self
    {
        if ($this->receivedDraws->removeElement($draw)) {
            if ($draw->getReceiver() === $this) {
                $draw->setReceiver(null);
            }
        }

        return $this;
    }

    public function getGroup(): ?Group
    {
        return $this->group;
    }

    public function setGroup(?Group $group): self
    {
        $this->group = $group;

        return $this;
    }
}
