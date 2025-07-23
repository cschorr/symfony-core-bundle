<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('var')
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        'declare_strict_types' => true,
        'concat_space' => false,
        'strict_comparison' => true,
        'strict_param' => true,

    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder)
;
