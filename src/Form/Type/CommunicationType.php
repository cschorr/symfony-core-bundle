<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class CommunicationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email Address',
                'required' => false,
                'attr' => [
                    'placeholder' => 'example@company.com',
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new Assert\Email([
                        'message' => 'Please enter a valid email address.',
                    ]),
                ],
            ])
            ->add('phone', TelType::class, [
                'label' => 'Phone Number',
                'required' => false,
                'attr' => [
                    'placeholder' => '+1 (555) 123-4567',
                    'class' => 'form-control',
                ],
            ])
            ->add('cell', TelType::class, [
                'label' => 'Mobile/Cell Phone',
                'required' => false,
                'attr' => [
                    'placeholder' => '+1 (555) 987-6543',
                    'class' => 'form-control',
                ],
            ])
            ->add('url', UrlType::class, [
                'label' => 'Website URL',
                'required' => false,
                'attr' => [
                    'placeholder' => 'https://www.company.com',
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new Assert\Url([
                        'message' => 'Please enter a valid URL.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'inherit_data' => true,
        ]);
    }
}
