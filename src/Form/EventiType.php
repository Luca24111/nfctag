<?php

namespace App\Form;

use App\Entity\Eventi;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventiType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nome', TextType::class, [
                'label' => 'Nome evento',
                'attr' => [
                    'placeholder' => 'Es. Festa, Lancio prodotto...',
                    'class' => 'form-control'
                ]
            ])
            ->add('citta', TextType::class, [
                'label' => 'Luogo',
                'attr' => [
                    'placeholder' => 'Es. Milano, Roma...',
                    'class' => 'form-control'
                ]
            ])
            ->add('data', DateType::class, [
                'label' => 'Data',
                'widget' => 'single_text',
                'html5' => true,
                'attr' => [
                    'class' => 'form-control'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Eventi::class,
        ]);
    }
}
