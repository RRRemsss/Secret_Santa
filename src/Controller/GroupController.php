<?php

namespace App\Controller;

use App\Entity\Group;
use App\Form\GroupParticipantsType;
use App\Form\GroupType;
use App\Repository\GroupRepository;
use App\Service\GroupManagerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
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
 
         // Gestion de l'upload du fichier CSV
         if ($request->files->get('participantsCsv')) {
             $file = $request->files->get('participantsCsv');
             try {
                 $participantsData = $groupManager->importFromCsv($file);
                 // On crée un groupe avec les participants importés du CSV
                 $group = $groupManager->createGroup($participantsData);
 
                 return $this->redirectToRoute('group_compose_message', ['groupId' => $group->getId()]);
             } catch (FileException $e) {
                 $this->addFlash('error', 'Error uploading CSV file.');
             }
         }
 
         // Si le formulaire est soumis et valide
         if ($participantForm->isSubmitted() && $participantForm->isValid()) {
             // Récupérer les données du formulaire
             $participantsData = $participantForm->getData()['participants'];
 
             // Crée un nouveau groupe avec les participants saisis
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