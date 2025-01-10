<?php

// src/Form/MediaType.php
namespace App\Form;

use App\Entity\Article;
use App\Entity\Media;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class MediaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('file', FileType::class, [
                'label' => 'Fichier',
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp'
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image file',
                    ])
                ],
            ])
            ->add('name', TextType::class, [
                'label' => 'Nom'
            ])
            ->add('altText', TextType::class, [
                'label' => 'Texte alternatif',
                'required' => false,
            ])
            ->add('caption', TextareaType::class, [
                'label' => 'Légende',
                'required' => false,
            ])
            ->add('article', EntityType::class, [
                'class' => Article::class,
                'choice_label' => 'title',
                'label' => 'Article associé',
                'required' => false,
                'placeholder' => 'Sélectionner un article'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Media::class,
        ]);
    }
}
