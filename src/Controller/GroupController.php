<?php

namespace App\Controller;

use App\Form\ComposeMessageType;
use App\Form\GroupParticipantsType;
use App\Form\GroupType;
use App\Service\EmailService;
use App\Service\GroupManagerService;
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
                    $request->getSession()->set('participantsData', $participantsData);
                    return $this->redirectToRoute('group_compose_message', ['groupId' => $group->getId()]);
                }
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de l\'importation du fichier : ' . $e->getMessage());
            }
        }

        if ($participantForm->isSubmitted() && $participantForm->isValid()) {
            // Process the submitted form data
            $formData = $request->request->all('participant'); // Retrieve "participant" data as an associative array
            // Prepare data for session storage
            $participantsArray = [];
            foreach ($formData as $index => $participant) {
                $participantsArray[] = [
                    'id' => $index + 1,
                    'name' => $participant['name'] ?? null,
                    'email' => $participant['email'] ?? null,
                    'exclusion' => isset($participant['exclusion']) ? explode(',', $participant['exclusion']) : [],
                ];
            }
        
            // Create the group and store participants in session
            $group = $groupManager->createGroup($participantsArray);
            $request->getSession()->set('participantsData', $participantsArray);
        
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
            return $this->redirectToRoute('group_review_draw', ['groupId' => $group->getId()]);
        }

        return $this->render('emails/compose_message.html.twig', [
            'messageForm' => $messageForm->createView(),
            'group' => $group,
        ]);
    }

    #[Route('/reviewDraw/{groupId}', name: 'review_draw', methods: ['GET', 'POST'])]
    public function reviewDraw(Request $request, int $groupId, GroupManagerService $groupManager, EmailService $emailService): Response
    {
        $group = $groupManager->findGroupById($groupId);

        if (!$group) {
            throw $this->createNotFoundException('Groupe non trouvé');
        }

        // Retrieve the subject and body from the session
        $subject = $request->getSession()->get('email_subject');
        $body = $request->getSession()->get('email_body');

        // Retrieve the participants and their exclusions from the session
        $participantsData = $request->getSession()->get('participantsData', []);

        // Mapping IDs to name of the participants
        $nameById = [];
        foreach ($participantsData as $participant) {
            // Ensure that the 'id' key exists
            if (isset($participant['id'])) {
                $nameById[$participant['id']] = $participant['name'];  // Associate each ID to their participant's name
            }
        }

        // Transform exclusions to use the names instead of the IDs.
        foreach ($participantsData as &$participant) {
            $exclusionNames = [];
            foreach ($participant['exclusion'] as $excludedId) {
                // Varifyong if the name asscicated to the ID exist
                if (isset($nameById[$excludedId])) {
                    $exclusionNames[] = $nameById[$excludedId]; // Get name
                }
            }
            $participant['exclusion_names'] = $exclusionNames; // Add name to th exclusions
        }

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
            'participants' => $participantsData,
        ]);
    }
    // #[Route('/summaryDraw/{groupId}', name: 'summary_draw', methods: ['GET', 'POST'])]
    // public function summaryDraw(Request $request, int $groupId, GroupManagerService $groupManager, EmailService $emailService): Response
    // {
       
    // }
}
