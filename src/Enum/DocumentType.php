<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Enum;

enum DocumentType: string
{
    case CONTRACT = 'contract';
    case BRIEF = 'brief';
    case DELIVERABLE = 'deliverable';
    case INVOICE = 'invoice';
    case OFFER = 'offer';
    case SIGNED_DOCUMENT = 'signed_document';
    case OTHER = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::CONTRACT => 'Contract',
            self::BRIEF => 'Brief',
            self::DELIVERABLE => 'Deliverable',
            self::INVOICE => 'Invoice',
            self::OFFER => 'Offer',
            self::SIGNED_DOCUMENT => 'Signed Document',
            self::OTHER => 'Other',
        };
    }
}
