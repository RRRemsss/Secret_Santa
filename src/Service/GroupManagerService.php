<?php

namespace App\Service;

use App\Entity\Group;
use App\Entity\Participant;
use Doctrine\ORM\EntityManagerInterface;

class GroupManagerService
{
    private $entityManager;

    // On injecte l'EntityManager pour la gestion des entités dans la base de données
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    // Méthode pour créer un groupe à partir d'un tableau de participants
    public function createGroup(array $participantsData): Group
    {
        // Crée une nouvelle entité Group
        $group = new Group();

        // Pour chaque participant, on crée une entité Participant
        foreach ($participantsData as $data) {
            $participant = new Participant();
            $participant->setName($data['name']);
            $participant->setEmail($data['email']);
            $participant->setGroup($group);  // Liaison avec le groupe
            $group->addParticipant($participant);  // Ajout du participant au groupe
        }

        // On persiste le groupe et les participants dans la base de données
        $this->entityManager->persist($group);
        $this->entityManager->flush();

        return $group;
    }

    // Méthode pour importer les participants à partir d'un fichier CSV
    public function importFromCsv($file): array
    {
        $participants = [];

        // Lecture du fichier CSV
        if (($handle = fopen($file->getRealPath(), 'r')) !== false) {
            while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                $participants[] = ['name' => $data[0], 'email' => $data[1]];
            }
            fclose($handle);
        }

        return $participants;
    }
}
