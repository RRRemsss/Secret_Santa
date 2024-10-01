<?php

namespace App\Service;

use App\Entity\Group;
use App\Entity\Participant;
use App\Repository\ParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class GroupManagerService
{
    private $entityManager;
    private $participantRepository;

    // On injecte l'EntityManager pour la gestion des entités dans la base de données
    public function __construct(EntityManagerInterface $entityManager, ParticipantRepository $participantRepository)
    {
        $this->entityManager = $entityManager;
        $this ->participantRepository = $participantRepository;
    }

    // Méthode pour créer un groupe à partir d'un tableau de participants
    public function createGroup(array $participantsData)
    {
        $group = new Group();

        foreach ($participantsData as $data) {
            $participant = new Participant();
            $participant->setName($data['name']);
            $participant->setEmail($data['email']);
            
            // Associer les exclusions au participant
            if (!empty($data['exclusion'])) {
                foreach ($data['exclusion'] as $excludedId) {
                    // Tu pourrais avoir besoin d'une méthode pour associer l'exclusion par ID
                    $excludedParticipant = $this->findParticipantById($excludedId);
                    if ($excludedParticipant) {
                        $participant->addExclusion($excludedParticipant);
                    }
                }
            }

            $group->addParticipant($participant);
        }

        // Persister et enregistrer le groupe
        $this->entityManager->persist($group);
        $this->entityManager->flush();

        return $group;
    }

    private function findParticipantById($id)
    {
        // Implémente la logique pour retrouver un participant par ID (par exemple, via une base de données ou les données importées)
        return $this->participantRepository->find($id);
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

                    // Gestion des exclusions
                    $exclusionString = $data[2] ?? '';  // Récupère la colonne "exclusion" ou une chaîne vide si elle n'existe pas
                    $exclusionArray = array_filter(array_map('trim', explode(',', $exclusionString))); // Transforme la chaîne en tableau, enlève les espaces

                    // Ajouter le participant avec les exclusions
                    $participants[] = [
                        'name' => $name,
                        'email' => $email,
                        'exclusion' => $exclusionArray // Ajoute les exclusions comme un tableau d'IDs
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

    try {
        // Chargement du fichier Excel
        $spreadsheet = IOFactory::load($file->getRealPath());
        $worksheet = $spreadsheet->getActiveSheet();

        // Itérer sur les lignes du tableau
        foreach ($worksheet->getRowIterator() as $row) {
            // Créer un itérateur pour les cellules
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false); // Obtenir toutes les cellules, même vides

            $data = [];
            foreach ($cellIterator as $cell) {
                $data[] = $cell->getValue();
            }

            // Sauter la première ligne (en-tête) et vérifier le format
            if ($row->getRowIndex() > 1 && count($data) >= 2) { // On commence à 2 pour ignorer l'en-tête
                $exclusions = isset($data[2]) ? explode(',', $data[2]) : [];

                $participants[] = [
                    'name' => $data[0],
                    'email' => $data[1],
                    'exclusion' => array_map('trim', $exclusions), // Nettoyer et stocker les exclusions comme un tableau
                ];
            }
        }
    } catch (\Exception $e) {
        throw new \Exception('Erreur lors de la lecture du fichier Excel : ' . $e->getMessage());
    }

    return $participants;
   }
}


