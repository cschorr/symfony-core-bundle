<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('var')
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        'declare_strict_types' => true,
        'strict_comparison' => true,
        'strict_param' => true,
        'concat_space' => ['spacing' => 'one'], // Use spaces around concatenation operator
        'phpdoc_align' => false, // Don't align PHPDoc tags
        'method_argument_space' => [
            'on_multiline' => 'ensure_fully_multiline',
        ],
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder)
;
