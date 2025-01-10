<?php

// src/Form/LanguageType.php
namespace App\Form;

use App\Entity\Language;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LanguageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'label' => 'Code (ex: fr)',
                'attr' => ['maxlength' => 2]
            ])
            ->add('name', TextType::class, [
                'label' => 'Nom'
            ])
            ->add('locale', TextType::class, [
                'label' => 'Locale (ex: fr_FR)'
            ])
            ->add('default', CheckboxType::class, [
                'label' => 'Langue par défaut',
                'required' => false
            ])
            ->add('active', CheckboxType::class, [
                'label' => 'Actif',
                'required' => false
            ])
            ->add('position', NumberType::class, [
                'label' => 'Position'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Language::class,
        ]);
    }
}
