<?php

require_once '../../../init.php';
$finder = PhpCsFixer\Finder::create()
    ->in(\IPS\ROOT_PATH);

$config = new PhpCsFixer\Config();
return $config->setRules([
    '@PSR12' => true,
    '@PSR12:risky' => true,
    'global_namespace_import' => ['import_classes' => true, 'import_constants' => true, 'import_functions' => true],
    'cast_spaces' => ['space' => 'single'],
    'modernize_types_casting' => true,
    'strict_param' => true,
    'array_syntax' => ['syntax' => 'short'],
    'ordered_imports' => ['sort_algorithm' => 'length', 'imports_order' => ['class', 'function', 'const']]
])->setFinder($finder)->setRiskyAllowed(true);