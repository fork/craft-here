<?php

use craft\ecs\SetList;
use PhpCsFixer\Fixer\FunctionNotation\FunctionDeclarationFixer;
use PhpCsFixer\Fixer\Whitespace\ArrayIndentationFixer;
use PhpCsFixer\Fixer\Whitespace\MethodChainingIndentationFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return static function (ECSConfig $ecsConfig): void {
    $paths = [
        __DIR__ . '/src',
        __FILE__,
    ];

    $ecsConfig->paths($paths);
    $ecsConfig->parallel();
    $ecsConfig->sets([SetList::CRAFT_CMS_4]);

    $ecsConfig->rule(ArrayIndentationFixer::class);
    $ecsConfig->rule(MethodChainingIndentationFixer::class);
    $ecsConfig->ruleWithConfiguration(FunctionDeclarationFixer::class, [
        'closure_function_spacing' => FunctionDeclarationFixer::SPACING_ONE,
    ]);
};
