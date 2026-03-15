<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Form;

use App\Application\Player\DTO\UpdateProfileDTO;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class UpdateProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('pseudo', TextType::class, [
                'label' => 'form.pseudo',
                'disabled' => true,
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
            ->add('newsletter', CheckboxType::class, [
                'label' => 'form.newsletter',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UpdateProfileDTO::class,
            'translation_domain' => 'player',
        ]);
    }
}
