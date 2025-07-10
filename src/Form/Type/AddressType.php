<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
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
                    'class' => 'form-control'
                ]
            ])
            ->add('zip', TextType::class, [
                'label' => 'ZIP/Postal Code',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Enter ZIP/postal code',
                    'class' => 'form-control'
                ]
            ])
            ->add('city', TextType::class, [
                'label' => 'City',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Enter city',
                    'class' => 'form-control'
                ]
            ])
            ->add('countryCode', ChoiceType::class, [
                'label' => 'Country',
                'required' => false,
                'placeholder' => 'Select country',
                'choices' => $this->getCountryChoices(),
                'attr' => [
                    'class' => 'form-select'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null, // This will be used as embedded form
            'inherit_data' => true, // This allows the form to use the parent entity's data
        ]);
    }

    /**
     * Get common country choices as ISO 3166-1 alpha-2 codes
     */
    private function getCountryChoices(): array
    {
        return [
            'Germany' => 'DE',
            'United States' => 'US',
            'United Kingdom' => 'GB',
            'France' => 'FR',
            'Italy' => 'IT',
            'Spain' => 'ES',
            'Netherlands' => 'NL',
            'Belgium' => 'BE',
            'Austria' => 'AT',
            'Switzerland' => 'CH',
            'Poland' => 'PL',
            'Czech Republic' => 'CZ',
            'Denmark' => 'DK',
            'Sweden' => 'SE',
            'Norway' => 'NO',
            'Finland' => 'FI',
            'Portugal' => 'PT',
            'Ireland' => 'IE',
            'Luxembourg' => 'LU',
            'Canada' => 'CA',
            'Australia' => 'AU',
            'Japan' => 'JP',
            'South Korea' => 'KR',
            'China' => 'CN',
            'India' => 'IN',
            'Brazil' => 'BR',
            'Mexico' => 'MX',
            'Argentina' => 'AR',
            'Chile' => 'CL',
            'South Africa' => 'ZA',
            'Russia' => 'RU',
            'Turkey' => 'TR',
            'Israel' => 'IL',
            'United Arab Emirates' => 'AE',
            'Saudi Arabia' => 'SA',
            'Singapore' => 'SG',
            'Malaysia' => 'MY',
            'Thailand' => 'TH',
            'Indonesia' => 'ID',
            'Philippines' => 'PH',
            'Vietnam' => 'VN',
            'New Zealand' => 'NZ',
        ];
    }
}
