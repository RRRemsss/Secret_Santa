describe('Gestion des participants - Champs manquants', () => {
    it('Affiche une erreur si un champ est vide ou invalide', () => {
        cy.visit('http://localhost:80/secret_santa/public/group/setup/participant');

        // Laisser le premier participant sans nom
        cy.get('input[name="participantForm[participants][0][email]"]').type('remy@example.com');

        // Ajouter un deuxième participant avec un email manquant
        cy.get('#addRow').click();
        cy.get('input[name="participantForm[participants][1][name]"]').type('Sergio');

        // Soumettre le formulaire
        cy.get('button[type="submit"]').click();

        // Attendre que les erreurs apparaissent (500ms est un délai raisonnable pour laisser le JS faire son travail)
        cy.wait(500);

        // Vérifier que le champ "Nom" pour le premier participant a la classe 'is-invalid'
        cy.get('input[name="participantForm[participants][0][name]"]')
            .should('have.class', 'is-invalid');

        // Vérifier que le champ "Nom" pour le deuxième participant a aussi la classe 'is-invalid'
        cy.get('input[name="participantForm[participants][1][name]"]')
            .should('have.class', 'is-invalid');

        // Vérifier que le message d'erreur pour le champ "Nom" est bien visible
        cy.contains('Le nom est requis.').should('be.visible');

        // Vérifier que le message d'erreur pour le champ "Email" est bien visible si l'email est invalide
        cy.contains('Veuillez fournir une adresse email valide.').should('be.visible');
    });
});
