<?php

namespace App\Entity;

use App\Repository\EmailLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity(repositoryClass: EmailLogRepository::class)]
class EmailLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $participantEmail = null;

    #[ORM\Column(length: 20)]
    private ?string $status = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $sendAt = null;

    #[ORM\ManyToOne(inversedBy: 'emailLogs')]
    private ?Group $group = null; 

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getParticipantEmail(): ?string
    {
        return $this->participantEmail;
    }

    public function setParticipantEmail(string $participantEmail): static
    {
        $this->participantEmail = $participantEmail;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getSendAt(): ?\DateTimeInterface
    {
        return $this->sendAt;
    }

    public function setSendAt(\DateTimeInterface $sendAt): static
    {
        $this->sendAt = $sendAt;

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
