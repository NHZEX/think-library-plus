<?php
declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in([
        'src',
        'legacy',
        'bin',
    ]);
$config = new PhpCsFixer\Config();

return $config
    ->setRules([
        '@PSR12'                     => true,
        '@PHP80Migration'            => true,
        'normalize_index_brace'      => true,
        'global_namespace_import'    => ['import_classes' => false, 'import_constants' => false, 'import_functions' => false],
        'operator_linebreak'         => ['only_booleans' => true, 'position' => 'beginning'],
        'standardize_not_equals'     => true,
        'unary_operator_spaces'      => true,
        // risky
        'native_function_invocation' => ['include' => ['@compiler_optimized'], 'scope' => 'all', 'strict' => true],
        'function_to_constant'       => true,
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder);
