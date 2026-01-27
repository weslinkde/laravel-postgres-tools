<?php

use Weslinkde\PostgresTools\Commands\CreateSnapshot;

it('has exclude-table-data option in signature', function () {
    $command = new CreateSnapshot;

    $definition = $command->getDefinition();

    expect($definition->hasOption('exclude-table-data'))->toBeTrue();
    expect($definition->getOption('exclude-table-data')->isArray())->toBeTrue();
});

it('has exclude option in signature', function () {
    $command = new CreateSnapshot;

    $definition = $command->getDefinition();

    expect($definition->hasOption('exclude'))->toBeTrue();
    expect($definition->getOption('exclude')->isArray())->toBeTrue();
});

it('has table option in signature', function () {
    $command = new CreateSnapshot;

    $definition = $command->getDefinition();

    expect($definition->hasOption('table'))->toBeTrue();
    expect($definition->getOption('table')->isArray())->toBeTrue();
});
