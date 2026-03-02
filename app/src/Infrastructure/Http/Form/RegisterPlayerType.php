<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Form;

use App\Application\Player\DTO\RegisterPlayerDTO;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class RegisterPlayerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('login', TextType::class, [
                'label' => 'form.login',
                'attr' => [
                    'placeholder' => 'form.login_help',
                ],
            ])
            ->add('pseudo', TextType::class, [
                'label' => 'form.pseudo',
                'attr' => [
                    'placeholder' => 'form.pseudo_help',
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'form.email',
                'attr' => [
                    'placeholder' => 'form.email_placeholder',
                ],
            ])
            ->add('discord', TextType::class, [
                'label' => 'form.discord',
                'required' => false,
                'attr' => [
                    'placeholder' => 'form.discord_placeholder',
                ],
            ])
            ->add('rulesAccepted', CheckboxType::class, [
                'label' => 'form.rules_accepted',
            ])
            ->add('newsletter', CheckboxType::class, [
                'label' => 'form.newsletter',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RegisterPlayerDTO::class,
            'translation_domain' => 'player',
        ]);
    }
}
