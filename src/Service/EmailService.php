<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Twig\Environment; // Pour le templating avec Twig
use App\Entity\Group;

class EmailService
{
    private $mailer;
    private $twig;

    public function __construct(MailerInterface $mailer, Environment $twig)
    {
        $this->mailer = $mailer;
        $this->twig = $twig;
    }

    /**
     * Crée un template d'email à partir des données fournies.
     *
     * @param Group $group Le groupe concerné
     * @param string $subject Le sujet de l'email
     * @param string $body Le corps du message fourni par l'utilisateur
     * @return string Le contenu de l'email généré
     */
    public function createEmailTemplate(Group $group, string $subject, string $body): string
    {
        // Utilisation de Twig pour générer le contenu de l'email avec les informations du groupe
        return $this->twig->render('emails/group_notification.html.twig', [
            'group' => $group,
            'subject' => $subject,
            'body' => $body,
        ]);
    }

    /**
     * Envoie un email aux participants d'un groupe.
     *
     * @param Group $group Le groupe dont les participants recevront l'email
     * @param string $subject Le sujet de l'email
     * @param string $body Le corps de l'email
     */
    public function sendGroupEmail(Group $group, string $subject, string $body): void
    {
        foreach ($group->getParticipants() as $participant) {
            $email = (new TemplatedEmail())
            ->from('no-reply@demomailtrap.com')  
            ->to($participant->getEmail())
            ->subject($subject) 
            ->htmlTemplate('emails/group_email.html.twig') // Template HTML pour l'email
            ->context([
                'participant' => $participant, // Passer des données au template
                'body' => $body,
                'subject' => $subject,
            ]);

        // Envoi de l'email
        $this->mailer->send($email);
    
    }
    }
}
