<?php

$finder = PhpCsFixer\Finder::create()
    ->in('src')
    ->in('test')
    ->in('dev');
$config = new PhpCsFixer\Config();

$config->setFinder($finder)
    ->setRules([
        '@Symfony'                               => true,
        '@Symfony:risky'                         => true,
        '@PHP71Migration'                        => true,
        '@PHP71Migration:risky'                  => true,
        '@PHPUnit60Migration:risky'              => true,
        'align_multiline_comment'                => ['comment_type' => 'all_multiline'],
        'array_indentation'                      => true,
        'array_syntax'                           => ['syntax' => 'short'],
        'binary_operator_spaces'                 => ['default' => 'align_single_space_minimal'],
        'class_definition'                       => [
            'single_item_single_line'             => true,
            'multi_line_extends_each_single_line' => true,
        ],
        'combine_consecutive_issets'             => true,
        'combine_consecutive_unsets'             => true,
        'comment_to_phpdoc'                      => true,
        'compact_nullable_typehint'              => true,
        'concat_space'                           => ['spacing'  => 'one'],
        'date_time_immutable'                    => true,
        'escape_implicit_backslashes'            => ['single_quoted' => true],
        'explicit_indirect_variable'             => true,
        'explicit_string_variable'               => true,
        'fully_qualified_strict_types'           => true,
        'header_comment'                         => [
            'comment_type' => 'PHPDoc',
            'header'       => "Copyright © Magento, Inc. All rights reserved.\nSee COPYING.txt for license details.",
            'location'     => 'after_open',
            'separate'     => 'bottom',
        ],
        'heredoc_to_nowdoc'                      => true,
        'linebreak_after_opening_tag'            => true,
        'list_syntax'                            => ['syntax' => 'short'],
        'logical_operators'                      => true,
        'method_chaining_indentation'            => true,
        'multiline_comment_opening_closing'      => true,
        'multiline_whitespace_before_semicolons' => true,
        'native_function_invocation'             => [
            'include' => ['@compiler_optimized'],
            'scope'   => 'namespaced',
        ],
        'no_alternative_syntax'                  => true,
        'no_extra_blank_lines'                   => [
            'tokens' => [
                'case',
                'continue',
                'curly_brace_block',
                'default',
                'extra',
                'parenthesis_brace_block',
                'return',
                'square_brace_block',
                'switch',
                'throw',
                'use',
            ],
        ],
        'no_null_property_initialization'        => true,
        'no_php4_constructor'                    => true,
        'no_superfluous_elseif'                  => true,
        'no_superfluous_phpdoc_tags'             => true,
        'no_unreachable_default_argument_value'  => true,
        'no_useless_else'                        => true,
        'no_useless_return'                      => true,
        'ordered_class_elements'                 => ['sort_algorithm' => 'alpha'],
        'ordered_imports'                        => ['imports_order' => ['const', 'class', 'function']],
        'php_unit_strict'                        => true,
        'php_unit_set_up_tear_down_visibility'   => true,
        'phpdoc_add_missing_param_annotation'    => ['only_untyped' => false],
        'phpdoc_order'                           => true,
        'phpdoc_types_order'                     => ['sort_algorithm' => 'none', 'null_adjustment' => 'always_last'],
        'psr_autoloading'                        => ['dir' => 'src'],
        'return_assignment'                      => true,
        'simplified_null_return'                 => true,
        'yoda_style'                             => false,
    ]);
return $config;
