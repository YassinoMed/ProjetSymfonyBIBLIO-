<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Copy;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @extends AbstractType<Copy>
 */
final class CopyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('barcode', TextType::class, [
                'label' => 'Code-barres',
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 50)],
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Disponible' => 'disponible',
                    'Emprunté' => 'emprunté',
                    'Réservé' => 'réservé',
                    'En réparation' => 'en_réparation',
                    'Perdu' => 'perdu',
                    'Consultation sur place' => 'consultation_sur_place',
                ],
            ])
            ->add('condition', TextType::class, [
                'label' => 'État',
                'data' => 'bon',
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 50)],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Copy::class,
        ]);
    }
}
