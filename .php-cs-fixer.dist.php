<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: 2025 Mikael Nordin <kano@sunet.se>
// SPDX-License-Identifier: AGPL-3.0-or-later

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = (new Finder())
    ->in(__DIR__ . '/lib');

return (new Config())
    ->setRiskyAllowed(false)
    ->setRules([
        '@PSR12' => true,
        // Keep empty bodies (e.g. promoted-property constructors) on one line.
        'single_line_empty_body' => true,
    ])
    ->setFinder($finder);
