<?php

namespace App\Form\Field;

use App\Form\Type\AddressType;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\FieldTrait;
use Symfony\Component\Form\Extension\Core\Type\FormType;

final class AddressField implements FieldInterface
{
    use FieldTrait;

    public const OPTION_WIDGET = 'widget';

    public static function new(string $propertyName, ?string $label = null): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setTemplateName('crud/field/address')
            ->setFormType(AddressType::class)
            ->setCustomOption(self::OPTION_WIDGET, 'default');
    }

    public function setWidget(string $widget): self
    {
        $this->setCustomOption(self::OPTION_WIDGET, $widget);

        return $this;
    }
}
