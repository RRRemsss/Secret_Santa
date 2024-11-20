<?php

namespace App\Service;

use App\Entity\Draw;
use App\Entity\Group;
use App\Entity\Participant;
use App\Repository\GroupRepository;
use App\Repository\ParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class GroupManagerService
{
    private $entityManager;
    private $participantRepository;
    private $groupRepository;
    

    // On injecte l'EntityManager pour la gestion des entités dans la base de données
    public function __construct(EntityManagerInterface $entityManager, ParticipantRepository $participantRepository, GroupRepository $groupRepository)
    {
        $this->entityManager = $entityManager;
        $this ->participantRepository = $participantRepository;
        $this->groupRepository = $groupRepository;
    }

    // Méthode pour générer un numéro de tirage
    private function generateDrawNumber(): string
    {
        // Format de la date : année (2 chiffres), mois (2 chiffres), jour (2 chiffres)
        $datePart = date('ymd');
        // Générer un nombre aléatoire de 4 chiffres
        $randomPart = mt_rand(0000, 9999);
        // Combiner la date et le nombre aléatoire
        return $datePart . $randomPart;
    }
   

    // Méthode pour créer un groupe à partir d'un tableau de participants
    public function createGroup(array $participantsData)
    {
        $group = new Group();

        // Générer et assigner le numéro de tirage
        $group->setDrawNumber($this->generateDrawNumber());

        foreach ($participantsData as $data) {
            $participant = new Participant();
            $participant->setName($data['name']);
            $participant->setEmail($data['email']);
            $group->addParticipant($participant);
           
            // Associer les exclusions au participant
            if (!empty($data['exclusion'])) {
                foreach ($data['exclusion'] as $excludedId) {
                    $excludedParticipant = $this->findParticipantById($excludedId);
                    if ($excludedParticipant) {
                        $participant->addExclusion($excludedParticipant);
                    }
                }
            }

            $this->entityManager->persist($participant);  
        }

        $group->setCreatedAt(new \DateTime());

        // Persister et enregistrer le groupe
        $this->entityManager->persist($group);
        $this->entityManager->flush();

        return $group;
    }

    // Méthode permettant de retrouver un groupe par ID (par exemple, via une base de données ou les données importées)
    public function findGroupById($id): ?Group
    {
        return $this->groupRepository->find($id);
    }

    //  Méthode permettant de retrouver un participant par ID (par exemple, via une base de données ou les données importées)
    private function findParticipantById($id)
    {
        return $this->participantRepository->find($id);
    }


    // Méthode pour importer les participants à partir d'un fichier CSV
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

   public function assignReceiversToGivers(Group $group): void
   {
    $participants = $group->getParticipants()->toArray();
    $maxAttempts = 100; // Limite des tentatives
    $attempt = 0;

    while ($attempt < $maxAttempts) {
        $assignments = [];
        $availableReceivers = $participants;

        try {
            foreach ($participants as $giver) {
                // Récupérer les exclusions du donneur
                $exclusions = $giver->getExclusions()->map(fn($exclusion) => $exclusion->getId())->toArray();
                $exclusions[] = $giver->getId(); // Ajouter le donneur lui-même

                // Filtrer les receveurs possibles
                $possibleReceivers = array_filter($availableReceivers, function ($receiver) use ($exclusions) {
                    return !in_array($receiver->getId(), $exclusions, true);
                });

                // Si aucun receveur possible, rejeter cette tentative
                if (empty($possibleReceivers)) {
                    throw new \Exception('Impossible de compléter l\'assignation.');
                }

                // Choisir un receveur au hasard parmi les candidats
                $receiver = $possibleReceivers[array_rand($possibleReceivers)];
                $assignments[$giver->getId()] = $receiver;

                // Retirer le receveur choisi des disponibles
                $availableReceivers = array_filter($availableReceivers, fn($r) => $r !== $receiver);
            }

            // Si toutes les assignations sont valides, créer les relations
            foreach ($assignments as $giverId => $receiver) {
                $giver = $this->entityManager->getRepository(Participant::class)->find($giverId);

                $draw = new Draw();
                $draw->setGiver($giver);
                $draw->setReceiver($receiver);
                $draw->setGroup($group);
                $this->entityManager->persist($draw);
            }

            $this->entityManager->flush();
            return; // Tirage réussi
        } catch (\Exception $e) {
                // Réessayer en cas d'échec
                $attempt++;
            }
        }

    throw new \Exception('Échec de l\'assignation après plusieurs tentatives.');
    }


    public function drawParticipants(array $participants): array
    {
        $results = []; // Résultat des tirages sous forme de paires [donneur => receveur]
        $availableReceivers = array_keys($participants); // Liste des indices disponibles pour le tirage

        foreach ($participants as $giverIndex => $giver) {
            // Récupérer les exclusions du donneur
            $exclusions = isset($giver['exclusion']) && is_array($giver['exclusion'])
                ? array_map('intval', $giver['exclusion'])
                : [];

            // Ajouter le donneur lui-même dans ses exclusions
            $exclusions[] = $giverIndex + 1;

            // Filtrer les receveurs possibles
            $possibleReceivers = array_filter($availableReceivers, function ($receiverIndex) use ($exclusions) {
                return !in_array($receiverIndex + 1, $exclusions, true); // Comparer à la base 1
            });

            // Si aucun receveur possible, le tirage est impossible
            if (empty($possibleReceivers)) {
                throw new \Exception("Impossible de compléter le tirage. Vérifiez les exclusions.");
            }

            // Choisir un receveur parmi les possibilités
            $receiverIndex = $possibleReceivers[array_rand($possibleReceivers)];
            $results[$giver['name']] = $participants[$receiverIndex]['name'];

            // Retirer le receveur choisi de la liste des receveurs disponibles
            unset($availableReceivers[array_search($receiverIndex, $availableReceivers)]);
        }

        return $results;
    }


}


