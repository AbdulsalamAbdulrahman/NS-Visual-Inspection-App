<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

test('page and email copy says Building Electrical Inspector, not contractor', function () {
    $prose = '/\b(the|a|an|each|every|to|by|or|for|of|your) contractors?\b(?!\.)/i';
    $found = [];

    $files = Finder::create()->files()->name(['*.svelte', '*.php'])->in([resource_path('js/pages'), resource_path('js/components'), app_path('Notifications')]);

    foreach ($files as $file) {
        foreach (explode("\n", $file->getContents()) as $number => $line) {
            if (preg_match($prose, $line) === 1 && preg_match('#^\s*(\*|//|/\*|<!--)#', $line) !== 1) {
                $found[] = $file->getFilename().':'.($number + 1);
            }
        }
    }

    expect($found)->toBe([]);
});

test('the rename leaves LECAN\'s registered name alone', function () {
    $files = Finder::create()->files()->name('*.svelte')->in(resource_path('js/pages/print'));

    foreach ($files as $file) {
        expect($file->getContents())->not->toContain('Licensed Electrical Building');
    }
});
