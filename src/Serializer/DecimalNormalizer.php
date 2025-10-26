<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Serializer;

use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Handles conversion of numeric JSON values to strings for DECIMAL database columns.
 *
 * This denormalizer intercepts API requests and converts integer/float values
 * to strings for properties that are stored as DECIMAL in the database.
 */
final class DecimalNormalizer implements DenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;

    private const DECIMAL_PROPERTIES = [
        'totalValue',
        'subtotal',
        'taxRate',
        'taxAmount',
        'totalAmount',
        'paidAmount',
    ];

    private const ALREADY_CALLED = 'DECIMAL_NORMALIZER_ALREADY_CALLED';

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        // Convert numeric values to strings for DECIMAL properties
        if (\is_array($data)) {
            foreach (self::DECIMAL_PROPERTIES as $property) {
                if (isset($data[$property]) && (\is_int($data[$property]) || \is_float($data[$property]))) {
                    $data[$property] = number_format((float) $data[$property], 2, '.', '');
                }
            }
        }

        // Mark this normalizer as already called to avoid infinite recursion
        $context[self::ALREADY_CALLED] = true;

        // Call the next denormalizer in the chain
        return $this->denormalizer->denormalize($data, $type, $format, $context);
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        // Avoid infinite recursion
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }

        // Support denormalization for our entities that have DECIMAL fields
        return \is_array($data)
            && (
                str_contains($type, 'Transaction')
                || str_contains($type, 'Offer')
                || str_contains($type, 'Invoice')
            );
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            '*' => false,
        ];
    }
}
