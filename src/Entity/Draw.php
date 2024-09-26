<?php

namespace App\Entity;

use App\Repository\DrawRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DrawRepository::class)]
class Draw
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'givenDraws')] // Mapped to the "giver" side in Participant
    private ?Participant $giver = null;

    #[ORM\ManyToOne(inversedBy: 'receivedDraws')] // Mapped to the "receiver" side in Participant
    private ?Participant $receiver = null;

    #[ORM\ManyToOne(inversedBy: 'draws')] // Mapped to the draws in Group
    private ?Group $group = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGiver(): ?Participant
    {
        return $this->giver;
    }

    public function setGiver(?Participant $giver): static
    {
        $this->giver = $giver;

        return $this;
    }

    public function getReceiver(): ?Participant
    {
        return $this->receiver;
    }

    public function setReceiver(?Participant $receiver): static
    {
        $this->receiver = $receiver;

        return $this;
    }

    public function getGroup(): ?Group
    {
        return $this->group;
    }

    public function setGroup(?Group $group): static
    {
        $this->group = $group;

        return $this;
    }
}
