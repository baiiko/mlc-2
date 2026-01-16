<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Form;

use App\Application\Player\DTO\ResetPasswordDTO;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ResetPasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('password', PasswordType::class, [
                'label' => 'reset_password.password',
                'attr' => [
                    'placeholder' => 'reset_password.password_placeholder',
                ],
            ])
            ->add('confirmPassword', PasswordType::class, [
                'label' => 'reset_password.confirm_password',
                'attr' => [
                    'placeholder' => 'reset_password.confirm_password_placeholder',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ResetPasswordDTO::class,
            'translation_domain' => 'player',
        ]);
    }
}
