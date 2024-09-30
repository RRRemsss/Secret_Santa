<?php

namespace App\Service;

use App\Entity\Group;
use App\Entity\Participant;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

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

    public function importFromCsv($file): array
    {
        $participants = [];
        $rowNumber = 0; // Pour suivre le numéro de ligne pour les erreurs

        // Ouverture du fichier CSV
        if (($handle = fopen($file->getRealPath(), 'r')) !== false) {
            while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                $rowNumber++;
                
                // Vérifier que la ligne contient au moins 2 colonnes (nom et email)
                if (count($data) >= 2) {
                    // Validation du nom et de l'email (par exemple, vérifier si l'email est valide)
                    $name = trim($data[0]);
                    $email = filter_var(trim($data[1]), FILTER_VALIDATE_EMAIL);
                    
                    if (!$email) {
                        throw new FileException("Ligne $rowNumber: L'adresse email '{$data[1]}' est invalide.");
                    }

                    $participants[] = [
                        'name' => $name,
                        'email' => $email,
                        'exclusion' => $data[2] ?? null  // Ajout de la colonne "exclusion" si elle existe
                    ];
                } else {
                    // Si la ligne est incorrecte, on peut lancer une exception
                    throw new FileException("Ligne $rowNumber: Format CSV invalide. Chaque ligne doit contenir au moins 2 colonnes (name et email).");
                }
            }
            fclose($handle);
        } else {
            throw new FileException("Impossible d'ouvrir le fichier CSV.");
        }

        return $participants;
    }

   // Méthode pour importer les participants à partir d'un fichier Excel
   public function importFromExcel($file): array
   {
       $participants = [];

       // Lecture du fichier Excel
       $spreadsheet = IOFactory::load($file->getRealPath());
       $sheet = $spreadsheet->getActiveSheet();
       $rows = $sheet->toArray();

       foreach ($rows as $row) {
           // Vérifier que la ligne contient au moins 2 colonnes (nom et email)
           if (count($row) >= 2) {
               $participants[] = ['name' => $row[0], 'email' => $row[1]];
           } else {
               // Optionnel : Vous pouvez ajouter une gestion des erreurs ici, par exemple en lançant une exception ou en ajoutant un message d'erreur
               throw new FileException('Invalid Excel format. Each line must contain at least 2 columns (name and email).');
           }
       }

       return $participants;
   }
}


