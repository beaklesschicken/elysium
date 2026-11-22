<?php

it('renders the coming-soon page on its own', function () {
    $html = view('coming-soon')->render();

    expect($html)
        ->toContain('Elysium Hospitality')
        ->toContain('coming soon')
        ->toContain('<meta name="robots" content="noindex">');
});

it('does not list any services', function (string $service) {
    expect(strtolower(view('coming-soon')->render()))->not->toContain(strtolower($service));
})->with([
    'Consulting',
    'Restaurant Evaluation',
    'COGS',
    'Permit Tracking',
    'Tip Tracking',
    'Local Service',
    'restaurant',
    'hotel',
]);

it('loads no scripts or app assets that need the framework running', function () {
    $html = view('coming-soon')->render();

    expect($html)->not->toContain('<script')
        ->not->toContain('/build/')
        ->not->toContain('livewire');
});
