<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Form;

use App\Application\Team\DTO\UpdateTeamDTO;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class UpdateTeamType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $readonly = $options['readonly'];

        $builder
            ->add('tag', TextType::class, [
                'label' => 'form.tag',
                'attr' => array_filter([
                    'placeholder' => 'form.tag_placeholder',
                    'readonly' => $readonly ?: null,
                ]),
            ])
            ->add('fullName', TextType::class, [
                'label' => 'form.fullname',
                'attr' => array_filter([
                    'placeholder' => 'form.fullname_placeholder',
                    'readonly' => $readonly ?: null,
                ]),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UpdateTeamDTO::class,
            'translation_domain' => 'team',
            'readonly' => false,
        ]);
    }
}
