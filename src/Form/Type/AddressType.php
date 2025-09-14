<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('street', TextType::class, [
                'label' => 'Street Address',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Enter street address',
                ],
            ])
            ->add('zip', TextType::class, [
                'label' => 'ZIP/Postal Code',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Enter ZIP/postal code',
                ],
            ])
            ->add('city', TextType::class, [
                'label' => 'City',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Enter city',
                ],
            ])
            ->add('countryCode', CountryType::class, [
                'label' => 'Country',
                'required' => false,
                'placeholder' => 'Select country',
                'alpha3' => false, // Use ISO 3166-1 alpha-2 codes (DE, US, etc.)
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'inherit_data' => true, // This allows the form to use the parent entity's data directly
        ]);
    }
}
