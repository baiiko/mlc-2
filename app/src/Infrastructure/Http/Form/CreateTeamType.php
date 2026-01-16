<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Form;

use App\Application\Team\DTO\CreateTeamDTO;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CreateTeamType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('tag', TextType::class, [
                'label' => 'form.tag',
                'attr' => [
                    'placeholder' => 'form.tag_placeholder',
                ],
            ])
            ->add('fullName', TextType::class, [
                'label' => 'form.fullname',
                'attr' => [
                    'placeholder' => 'form.fullname_placeholder',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CreateTeamDTO::class,
            'translation_domain' => 'team',
        ]);
    }
}
