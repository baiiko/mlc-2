<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Form;

use App\Application\Player\DTO\ActivateAccountDTO;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ActivateAccountType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('password', PasswordType::class, [
                'label' => 'activate.password',
                'attr' => [
                    'placeholder' => 'activate.password_placeholder',
                ],
            ])
            ->add('confirmPassword', PasswordType::class, [
                'label' => 'activate.confirm_password',
                'attr' => [
                    'placeholder' => 'activate.confirm_password_placeholder',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ActivateAccountDTO::class,
            'translation_domain' => 'player',
        ]);
    }
}
