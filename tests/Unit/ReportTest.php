<?php

declare(strict_types=1);

use Jfrintrop\EnvValidator\Report;

it('renders json with the valid flag first', function () {
    $decoded = json_decode(Report::json(false, ['errors' => ['A' => ['bad']]]), true);

    expect($decoded)->toBe(['valid' => false, 'errors' => ['A' => ['bad']]]);
});

it('renders one github annotation per message with escaping', function () {
    $output = Report::github(["50% done\nnext", 'second'], 'dir/.env,x:y', 'Title');

    expect($output)->toBe(
        "::error file=dir/.env%2Cx%3Ay,title=Title::50%25 done%0Anext\n::error file=dir/.env%2Cx%3Ay,title=Title::second"
    );
});

it('omits the file property when none is given', function () {
    expect(Report::github(['x']))->toBe('::error title=env-validator::x');
});

it('knows the supported formats', function () {
    expect(Report::isSupported('json'))->toBeTrue()
        ->and(Report::isSupported('github'))->toBeTrue()
        ->and(Report::isSupported('text'))->toBeTrue()
        ->and(Report::isSupported('xml'))->toBeFalse();
});
