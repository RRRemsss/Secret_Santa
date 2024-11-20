<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class GroupControllerTest extends WebTestCase
{
    // public function testSetupParticipantsWithValidCsvUpload()
    // {
    //     // Créez un client de test Symfony
    //     $client = static::createClient();

    //     // Préparez le fichier CSV de test
    //     $file = new UploadedFile(
    //         __DIR__.'/Fixtures/valid_participants.csv', // Le chemin vers le fichier CSV
    //         'valid_participants.csv', // Le nom du fichier
    //         'text/csv', // Le type MIME du fichier
    //         null, // La taille du fichier (optionnel)
    //         true // Est-ce un fichier valide ?
    //     );

    //     // Soumettre le formulaire avec le fichier CSV
    //     $crawler = $client->request('GET', '/group/setup/participant');
    //     $form = $crawler->selectButton('Télécharger le fichier excel ou csv')->form();
    //     $form['participantsCsv'] = $file;

    //     // Envoyer le formulaire
    //     $client->submit($form);

    //     // Vérifier si la redirection vers une autre page se produit
    //     $this->assertResponseRedirects('/group/compose_message');
    // }

    public function testSetupParticipantsWithValidFormSubmission()
    {
        $client = static::createClient();
        
        // Simuler un formulaire de participants
        $crawler = $client->request('GET', '/group/setup/participant');

        // Vérifiez si la page est chargée correctement
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('submit')->form();

        // Remplir les données du formulaire avec des participants et des exclusions
        $form['participantForm[participants][0][name]'] = 'Sergio';
        $form['participantForm[participants][0][email]'] = 'sergio@test.com';
        $form['participantForm[participants][0][exclusions]'] = '2';

        $form['participantForm[participants][1][name]'] = 'Rémy';
        $form['participantForm[participants][1][email]'] = 'remy@test.com';
        $form['participantForm[participants][1][exclusions]'] = '1';

        $form['participantForm[participants][2][name]'] = 'Alex';
        $form['participantForm[participants][2][email]'] = 'alex@test.com';
        $form['participantForm[participants][2][exclusions]'] = '4,2';

        $form['participantForm[participants][3][name]'] = 'Valentina';
        $form['participantForm[participants][3][email]'] = 'valentina@test.com';
        $form['participantForm[participants][3][exclusions]'] = '';

        // Soumettre le formulaire
        $crawler = $client->submit($form);

        // Vérifier que l'utilisateur est redirigé après un envoi réussi
        $this->assertResponseRedirects('/group/compose_message');
    }

    public function testSetupParticipantsWithExclusionError()
    {
        $client = static::createClient();

        // Simuler un formulaire de participants avec trop d'exclusions
        $crawler = $client->request('GET', '/group/setup/participant');
        $form = $crawler->selectButton('Envoyer')->form();

        // Remplir le formulaire avec des exclusions invalides
        $form['participantForm[participants][0][name]'] = 'Sergio';
        $form['participantForm[participants][0][email]'] = 'sergio@test.com';
        $form['participantForm[participants][0][exclusions]'] = '2,3,4';

        $form['participantForm[participants][1][name]'] = 'Rémy';
        $form['participantForm[participants][1][email]'] = 'remy@test.com';
        $form['participantForm[participants][1][exclusions]'] = '1,3,4';

        $form['participantForm[participants][2][name]'] = 'Alex';
        $form['participantForm[participants][2][email]'] = 'alex@test.com';
        $form['participantForm[participants][2][exclusions]'] = '1,2,4';

        $form['participantForm[participants][3][name]'] = 'Valentina';
        $form['participantForm[participants][3][email]'] = 'valentina@test.com';
        $form['participantForm[participants][3][exclusions]'] = '1,2,3';

        // Soumettre le formulaire
        $crawler = $client->submit($form);

        // Vérifier que le formulaire est renvoyé avec une erreur de validation des exclusions
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.flash-error', 'a trop d\'exclusions');
    }
}
