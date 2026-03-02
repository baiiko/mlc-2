<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Form;

use App\Application\Player\DTO\ChangePasswordDTO;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ChangePasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('currentPassword', PasswordType::class, [
                'label' => 'change_password.current_password',
                'attr' => [
                    'placeholder' => 'change_password.current_password_placeholder',
                ],
            ])
            ->add('newPassword', PasswordType::class, [
                'label' => 'change_password.new_password',
                'attr' => [
                    'placeholder' => 'change_password.new_password_placeholder',
                ],
            ])
            ->add('confirmPassword', PasswordType::class, [
                'label' => 'change_password.confirm_password',
                'attr' => [
                    'placeholder' => 'change_password.confirm_password_placeholder',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ChangePasswordDTO::class,
            'translation_domain' => 'player',
        ]);
    }
}
