<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

/**
 * Стиль кода пакета: PER-CS 2.0 с дополнениями — одинарные кавычки, union-форма nullable-типов
 * и явный вызов функций стандартной библиотеки из глобального пространства имён.
 *
 * Проверяется весь код пакета: исходники, тесты и сам этот файл. Каталоги `vendor` и `runtime`
 * исключены — их содержимое пакету не принадлежит.
 */
return new Config()
    ->setRiskyAllowed(true)
    ->setRules([
        '@PER-CS' => true,
        'single_quote' => true,
        'nullable_type_declaration' => ['syntax' => 'union'],
        'native_function_invocation' => [
            'include' => ['@all'],
            'scope' => 'all',
            'strict' => true,
        ],
    ])
    ->setCacheFile(__DIR__ . '/runtime/php-cs-fixer.cache')
    ->setFinder(
        new Finder()
            ->in(__DIR__)
            ->exclude(['vendor', 'runtime'])
            ->append([__FILE__]),
    )
;
