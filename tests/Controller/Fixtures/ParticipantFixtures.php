<?php
// src/DataFixtures/ParticipantFixtures.php
namespace App\DataFixtures;

use App\Entity\Participant;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

class ParticipantFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        // Créer des participants fictifs
        $participant1 = new Participant();
        $participant1->setName('Rémy')
                     ->setEmail('remy@example.com');

        $participant2 = new Participant();
        $participant2->setName('Sergio')
                     ->setEmail('sergio@example.com');
        
        $participant3 = new Participant();
        $participant3->setName('Alexis')
                     ->setEmail('alex@example.com');

        $participant4 = new Participant();
        $participant4->setName('Valentina')
                    ->setEmail('valen@example.com');

        // Persister les entités
        $manager->persist($participant1);
        $manager->persist($participant2);
        $manager->persist($participant3);
        $manager->persist($participant4);

        // Sauvegarder dans la base de données
        $manager->flush();
    }

    // public function load(ObjectManager $manager)
    // {
    //     for ($i = 1; $i <= 10; $i++) {
    //         $participant = new Participant();
    //         $participant->setName('Participant ' . $i)
    //                      ->setEmail('participant' . $i . '@example.com');
    //         $manager->persist($participant);
    //     }
    //     $manager->flush();
    // }
}
