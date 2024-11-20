describe('Gestion des participants - succés', () => {
    it('Ajoute un participant et soumet le formulaire', () => {
      // Accéder à la page
      cy.visit('http://localhost:80/secret_santa/public/group/setup/participant');
  
      // Remplir les champs du premier participant
      cy.get('input[name="participantForm[participants][0][name]"]').type('Rémy');
      cy.get('input[name="participantForm[participants][0][email]"]').type('remy@example.com');
      cy.get('input[name="participantForm[participants][0][exclusions]"]').type('2');
  
      // Ajouter un autre participant
      cy.get('input[name="participantForm[participants][1][name]"]').type('Sergio');
      cy.get('input[name="participantForm[participants][1][email]"]').type('sergio@example.com');
      cy.get('input[name="participantForm[participants][1][exclusions]"]').type('1');

      // Ajouter un autre participant
      cy.get('input[name="participantForm[participants][2][name]"]').type('Alex');
      cy.get('input[name="participantForm[participants][2][email]"]').type('alex@example.com');
      cy.get('input[name="participantForm[participants][2][exclusions]"]').type('1,4');

      // Ajouter un autre participant
      cy.get('#addRow').click();
      cy.get('input[name="participantForm[participants][3][name]"]').type('Valentina');
      cy.get('input[name="participantForm[participants][3][email]"]').type('valen@example.com');
      cy.get('input[name="participantForm[participants][3][exclusions]"]').type('2,3');
  
      // Soumettre le formulaire
      cy.get('button[type="submit"]').click();
  
      // Vérifier la présence d'un message de succès
      cy.contains('Message envoyé avec succès').should('be.visible');
    });
  });
  