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
                'required' => true,
                'attr' => ['placeholder' => 'Prénom du participant', 
                            'class' => 'form-control'],
            ])
            ->add('email', EmailType::class, [
                'label' => false,
                'required' => true,
                'attr' => ['placeholder' => 'Email du participant', 
                            'class' => 'form-control'],
            ])
            ->add('exclusions', TextType::class, [
                'required' => false,      // Les exclusions ne sont pas obligatoires
                'attr' => [
                    'placeholder' => 'Exclure les participants avec leurs indices séparés par une virgule', 
                    'class' => 'form-control'
                ],
                
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

