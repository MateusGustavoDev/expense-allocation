<?php

declare(strict_types=1);

use App\Services\Csv\CsvReader;

function csvFixture(string $content): string
{
    $path = tempnam(sys_get_temp_dir(), 'csv');
    file_put_contents($path, $content);

    return $path;
}

function readRows(string $content): array
{
    return iterator_to_array((new CsvReader)->rows(csvFixture($content)));
}

it('reads semicolon separated fields keyed by the line number', function () {
    expect(readRows("a;b;c\n1;2;3\n"))->toBe([1 => ['a', 'b', 'c'], 2 => ['1', '2', '3']]);
});

it('strips the UTF-8 BOM that Excel adds', function () {
    expect(readRows("\xEF\xBB\xBFdata;valor\n")[1][0])->toBe('data');
});

it('handles Windows line breaks', function () {
    expect(readRows("a;b\r\n1;2\r\n"))->toBe([1 => ['a', 'b'], 2 => ['1', '2']]);
});

it('skips blank lines without shifting the line numbers', function () {
    expect(array_keys(readRows("a\n\n   \nb\n")))->toBe([1, 4]);
});

it('trims the fields', function () {
    expect(readRows(" a ; b \n")[1])->toBe(['a', 'b']);
});

it('keeps a delimiter inside quoted fields', function () {
    expect(readRows("\"Consultoria; jurídica\";10\n")[1])->toBe(['Consultoria; jurídica', '10']);
});

it('converts files saved as Windows-1252 to UTF-8', function () {
    expect(readRows(mb_convert_encoding("Licença;Café\n", 'Windows-1252', 'UTF-8'))[1])->toBe(['Licença', 'Café']);
});

it('fails when the file cannot be read', function () {
    iterator_to_array((new CsvReader)->rows('/caminho/que/nao/existe.csv'));
})->throws(RuntimeException::class);
