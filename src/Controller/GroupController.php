<?php

namespace App\Controller;

use App\Entity\Group;
use App\Form\GroupParticipantsType;
use App\Form\GroupType;
use App\Service\GroupManagerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/group', name:'group_')]
class GroupController extends AbstractController
{
    #[Route('/setup/participant', name: 'group_setup_participants', methods: ['GET', 'POST'])]
    public function setupParticipants(Request $request, GroupManagerService $groupManager) {
         // Crée un formulaire pour ajouter les participants manuellement
         $participantForm = $this->createForm(GroupParticipantsType::class);
         $participantForm->handleRequest($request);
 
         // Gestion de l'upload du fichier CSV/Excel
         if ($request->files->get('participantsCsv')) {
            $file = $request->files->get('participantsCsv');
            
            try {
                $extension = $file->getClientOriginalExtension();
                if ($extension === 'csv') {
                    // Si c'est un CSV, utilisez la méthode d'importation CSV
                    $participantsData = $groupManager->importFromCsv($file);
                } elseif (in_array($extension, ['xls', 'xlsx'])) {
                    // Si c'est un fichier Excel, utilisez la méthode d'importation Excel
                    $participantsData = $groupManager->importFromExcel($file);
                } else {
                    throw new \Exception('Format de fichier non supporté. Veuillez uploader un fichier CSV ou Excel.');
                }
        
                if (empty($participantsData)) {
                    $this->addFlash('error', 'Le fichier est vide ou mal formaté. Veuillez vérifier.');
                } else {
                    $group = $groupManager->createGroup($participantsData);
                    return $this->redirectToRoute('group_compose_message', ['groupId' => $group->getId()]);
                }
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de l\'importation du fichier : ' . $e->getMessage());
            }
        }
 
        // Si le formulaire est soumis et valide
        if ($participantForm->isSubmitted() && $participantForm->isValid()) {
            // Récupérer les données du formulaire
            $participantsData = $participantForm->getData()['participants'];

            // Traiter les exclusions au format CSV (ou formulaire manuel)
            foreach ($participantsData as &$participant) {
                // Vérifier si des exclusions sont présentes
                if (!empty($participant['exclusion'])) {
                    // Transformer la chaîne des exclusions en tableau d'IDs
                    $participant['exclusion'] = array_filter(array_map('trim', explode(';', $participant['exclusion'])));
                } else {
                    // Si aucune exclusion, définir une exclusion vide
                    $participant['exclusion'] = [];
                }
            }

            // Crée un nouveau groupe avec les participants et leurs exclusions
            $group = $groupManager->createGroup($participantsData);

            return $this->redirectToRoute('group_compose_message', ['groupId' => $group->getId()]);
        }

        return $this->render('group/setup_participants.html.twig', [
            'participantForm' => $participantForm->createView(),
        ]);
    }


    #[Route('/composeMessage/{groupId}', name: 'group_compose_message', methods: ['GET', 'POST'])]
    public function composeMessage (Request $request, EntityManagerInterface $entityManager): Response
    {
        // Formulaire pour composer le sujet et le corps de l’email

        $group = new Group();
        $messageForm = $this->createForm(GroupType::class, $group);
        $messageForm->handleRequest($request);

        if ($messageForm->isSubmitted() && $messageForm->isValid()) {
            $entityManager->persist($group);
            $entityManager->flush();

            // Stocker les informations du message dans la base de données ou session
            return $this->redirectToRoute('group_review_draw', ['groupId' => $group->getId()]);
        }

        return $this->render('group/compose_message.html.twig', [
            'group' => $group,
            'messageForm' => $messageForm,
        ]);
    }

    #[Route('/reviewDraw/{groupId}', name: 'group_review_draw', methods: ['GET'])]
    public function reviewDraw (Request $request, EntityManagerInterface $entityManager): Response
    {
        // Afficher le récapitulatif des participants, exclusions, et du message
        $group = new Group();
        $reviewDrawForm = $this->createForm(GroupType::class, $group);
        $reviewDrawForm->handleRequest($request);

        if ($reviewDrawForm->isSubmitted() && $reviewDrawForm->isValid()) {
            $entityManager->persist($group);
            $entityManager->flush();

            // Passer à l’étape de l’envoi des emails

            return $this->redirectToRoute('group_send_emails', ['groupId' => $group->getId()]);
        }

        return $this->render('group/review_draw.html.twig', [
            'group' => $group,
            'reviewDrawForm' => $reviewDrawForm,
        ]);
    }

    #[Route('/composeMessage/{groupId}', name: 'group_compose_message', methods: ['GET', 'POST'])]
    public function summaryDraw (Group $group): Response
    {
        
        // Afficher un récapitulatif avec les participants et le résultat du tirage
        // Proposer à l'organisateur une option pour relancer un tirage ou modifier les paires

        return $this->render('group/summary_draw.html.twig', [
            'group' => $group,
        ]);
    }

   
}
