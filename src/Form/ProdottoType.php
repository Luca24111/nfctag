<?php
// src/Form/ProdottoType.php

namespace App\Form;

use App\Entity\Prodotto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ProdottoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nome prodotto',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Descrizione',
                'attr' => ['rows' => 4],
            ])
            ->add('scaffale', TextType::class, [
                'label' => 'Scaffale',
                'required' => false,
            ])
            ->add('immagine', FileType::class, [
                'label' => 'Immagine del prodotto',
                'mapped' => false, // fondamentale per la gestione manuale nel controller
                'required' => false,
                'attr' => ['accept' => 'image/*'],
                'constraints' => [
                    new File([
                        'maxSize' => '10M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Per favore, carica un\'immagine valida (JPEG, PNG, WebP)',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Prodotto::class,
        ]);
    }
}