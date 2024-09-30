<?php
namespace App\Form;

use App\Entity\Group;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;


class GroupParticipantsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // Un formulaire qui permet d'ajouter plusieurs participants (nom et email)
        $builder
        ->add('participants', CollectionType::class, [
            'entry_type' => ParticipantType::class,  // Utilise le ParticipantType pour chaque participant
            'entry_options' => ['label' => false],  // Désactive les labels pour chaque participant
            'allow_add' => true,  // Autorise l'ajout dynamique de participants
            'allow_delete' => true,  // Autorise la suppression dynamique
            'prototype' => true,  // Génère un prototype pour ajouter via JavaScript
            'by_reference' => false,  // Important pour ManyToMany ou OneToMany relations
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Group::class,
        ]);
        
    }
}
