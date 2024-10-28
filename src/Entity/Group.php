<?php

namespace App\Entity;

use App\Repository\GroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GroupRepository::class)]
#[ORM\Table(name: '`groups`')]
class Group
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column (length:10)]
    private ?string $drawNumber = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    /**
     * @var Collection<int, Draw>
     */
    #[ORM\OneToMany(mappedBy: 'group', targetEntity: Draw::class)]
    private Collection $draws;

    /**
     * @var Collection<int, Participant>
     */
    #[ORM\OneToMany(mappedBy: 'group', targetEntity: Participant::class)]
    private Collection $participants;

    /**
     * @var Collection<int, EmailLog>
     */
    #[ORM\OneToMany(mappedBy: 'group', targetEntity: EmailLog::class)]
    private Collection $emailLogs;

    public function __construct()
    {
        $this->draws = new ArrayCollection();
        $this->participants = new ArrayCollection();
        $this->emailLogs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDrawNumber(): ?string
    {
        return $this->drawNumber;
    }

    public function setDrawNumber(string $drawNumber): self
    {
        $this->drawNumber = $drawNumber;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }


    /**
     * @return Collection<int, Draw>
     */
    public function getDraws(): Collection
    {
        return $this->draws;
    }

    public function addDraw(Draw $draw): self
    {
        if (!$this->draws->contains($draw)) {
            $this->draws->add($draw);
            $draw->setGroup($this); 
        }

        return $this;
    }

    public function removeDraw(Draw $draw): self
    {
        if ($this->draws->removeElement($draw)) {
            // set the owning side to null (unless already changed)
            if ($draw->getGroup() === $this) { 
                $draw->setGroup(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Participant>
     */
    public function getParticipants(): Collection
    {
        return $this->participants;
    }

    public function addParticipant(Participant $participant): self
    {
        if (!$this->participants->contains($participant)) {
            $this->participants->add($participant);
            $participant->setGroup($this); 
        }

        return $this;
    }

    public function removeParticipant(Participant $participant): self
    {
        if ($this->participants->removeElement($participant)) {
            // set the owning side to null (unless already changed)
            if ($participant->getGroup() === $this) {
                $participant->setGroup(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, EmailLog>
     */
    public function getEmailLogs(): Collection
    {
        return $this->emailLogs;
    }

    public function addEmailLog(EmailLog $emailLog): self
    {
        if (!$this->emailLogs->contains($emailLog)) {
            $this->emailLogs->add($emailLog);
            $emailLog->setGroup($this); 
        }

        return $this;
    }

    public function removeEmailLog(EmailLog $emailLog): self
    {
        if ($this->emailLogs->removeElement($emailLog)) {
            // set the owning side to null (unless already changed)
            if ($emailLog->getGroup() === $this) {
                $emailLog->setGroup(null);
            }
        }

        return $this;
    }
}
