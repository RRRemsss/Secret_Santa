<?php

namespace App\Controller;

use App\Entity\Group;
use App\Form\ComposeMessageType;
use App\Form\GroupParticipantsType;
use App\Form\GroupType;
use App\Service\EmailService;
use App\Service\GroupManagerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/group', name:'group_')]
class GroupController extends AbstractController
{
    #[Route('/setup/participant', name: 'setup_participants', methods: ['GET', 'POST'])]
    public function setupParticipants(Request $request, GroupManagerService $groupManager) {
         // Create a form to manually add participants
         $participantForm = $this->createForm(GroupParticipantsType::class);
         $participantForm->handleRequest($request);
 
         // Handling the CSV/Excel file upload
         if ($request->files->get('participantsCsv')) {
            $file = $request->files->get('participantsCsv');
            
            try {
                $extension = $file->getClientOriginalExtension();
                if ($extension === 'csv') {
                    // If it's a CSV, use the CSV import method
                    $participantsData = $groupManager->importFromCsv($file);
                } elseif (in_array($extension, ['xls', 'xlsx'])) {
                    // If it's an Excel file, use the Excel import method
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
 
        if ($participantForm->isSubmitted() && $participantForm->isValid()) {
            // Retrieve data from the form
            $group = $participantForm->getData();
            $participantsData = $group->getParticipants();

            // Convert ArrayCollection to an array
            $participantsArray = [];
            foreach ($participantsData as $participant) {
                $exclusions = $participant->getExclusions();
                $exclusionIds = [];
                foreach ($exclusions as $exclusion) {
                    $exclusionIds[] = $exclusion->getId();
                }
                $participantsArray[] = [
                    'name' => $participant->getName(),
                    'email' => $participant->getEmail(),
                    'exclusion' => $exclusionIds,
                ];
            }

            // Assuming $participantsData is an ArrayCollection
            $participantsData = $group->getParticipants(); 
            $participantsDataArray = $participantsData->toArray();
            // Create a new group with participants and their exclusions
            $group = $groupManager->createGroup($participantsDataArray);

            return $this->redirectToRoute('group_compose_message', ['groupId' => $group->getId()]);
        }

        return $this->render('group/setup_participants.html.twig', [
            'participantForm' => $participantForm->createView(),
        ]);
    }


    #[Route('/compose_message/{groupId}', name: 'compose_message', methods: ['GET', 'POST'])]
    public function composeMessage(Request $request, int $groupId, GroupManagerService $groupManager, EmailService $emailService)
    {
        // Use the findGroupById method to retrieve the group
        $group = $groupManager->findGroupById($groupId);

        if (!$group) {
            throw $this->createNotFoundException('Groupe non trouvé');
        }

        // Create a form to compose the message
        $messageForm = $this->createForm(ComposeMessageType::class);
        $messageForm->handleRequest($request);

        if ($messageForm->isSubmitted() && $messageForm->isValid()) {
            $formData = $messageForm->getData();
            
            // Create an email template with subject and body
            $subject = $formData['subject'];
            $body = $formData['body'];

            // Temporarily store the subject and body in the session
            $request->getSession()->set('email_subject', $subject);
            $request->getSession()->set('email_body', $body);

            // Redirect to the next step (reviewDraw)
            return $this->redirectToRoute('review_draw', ['groupId' => $group->getId()]);
        }

        return $this->render('emails/compose_message.html.twig', [
            'messageForm' => $messageForm->createView(),
            'group' => $group,
        ]);
    }

    #[Route('/reviewDraw/{groupId}', name: 'review_draw', methods: ['GET'])]
    public function reviewDraw (Request $request, int $groupId, GroupManagerService $groupManager, EmailService $emailService): Response
    {
        $group = $groupManager->findGroupById($groupId);

        if (!$group) {
            throw $this->createNotFoundException('Groupe non trouvé');
        }

        // Retrieve the subject and body from the session
        $subject = $request->getSession()->get('email_subject');
        $body = $request->getSession()->get('email_body');

        // Create a form for reviewing the draw
        $reviewDrawForm = $this->createForm(GroupType::class, $group);
        $reviewDrawForm->handleRequest($request);

        if ($reviewDrawForm->isSubmitted() && $reviewDrawForm->isValid()) {
            // Send emails
            $emailService->sendGroupEmail($group, $subject, $body);

            // Redirect to the next step (summaryDraw)
            return $this->redirectToRoute('summary_draw', ['groupId' => $group->getId()]);
        }

        return $this->render('group/review_draw.html.twig', [
            'group' => $group,
            'reviewDrawForm' => $reviewDrawForm->createView(),
            'subject' => $subject,
            'body' => $body,
        ]);
    }
}
