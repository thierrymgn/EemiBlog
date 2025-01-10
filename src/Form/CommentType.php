<?php

namespace App\Form;

use App\Entity\Article;
use App\Entity\Comment;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('content', TextareaType::class, [
                'label' => 'Contenu',
                'attr' => ['rows' => 5]
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'En attente' => 'pending',
                    'Approuvé' => 'approved',
                    'Rejeté' => 'rejected',
                    'Spam' => 'spam'
                ]
            ])
            ->add('parent', EntityType::class, [
                'class' => Comment::class,
                'choice_label' => 'content',
                'required' => false,
                'label' => 'Réponse à',
                'placeholder' => 'Aucun parent'
            ])
            ->add('article', EntityType::class, [
                'class' => Article::class,
                'choice_label' => 'title',
                'label' => 'Article'
            ])
            ->add('publisher', EntityType::class, [
                'class' => User::class,
                'choice_label' => function(User $user) {
                    return $user->getEmail() . ' (' . $user->getFirstname() . ' ' . $user->getLastname() . ')';
                },
                'label' => 'Auteur'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Comment::class,
        ]);
    }
}
