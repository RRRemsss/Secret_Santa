<?php

namespace App\Form;

use App\Entity\Participant;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ParticipantType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => false,
                'attr' => ['placeholder' => 'Prénom du participant', 
                            'class' => 'form-control'],
            ])
            ->add('email', EmailType::class, [
                'label' => false,
                'attr' => ['placeholder' => 'Email du participant', 
                            'class' => 'form-control'],
            ])
            ->add('exclusions', EntityType::class, [
                'class' => Participant::class,
                'choice_label' => 'name',
                'multiple' => true,       // Permettre plusieurs exclusions
                'expanded' => true,       // Afficher sous forme de cases à cocher pour meilleure UX
                'required' => false,      // Les exclusions ne sont pas obligatoires
                'attr' => ['class' => 'form-control'],
                'by_reference' => false,  // Gère correctement les relations many-to-many
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Participant::class,
        ]);
    }
}

