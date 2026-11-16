<?php

namespace Ugarit\Installer\Console\Tests;

use PHPUnit\Framework\TestCase;
use Ugarit\Installer\Console\Support\Collection;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Ugarit\Installer\Console\NewCommand;
use function Ugarit\Installer\Console\Support\collect;

class CollectionTest extends TestCase
{
    public function test_collection_helpers_and_methods()
    {
        $c = collect(['a', 'b', 'c']);
        $this->assertInstanceOf(Collection::class, $c);
        $this->assertEquals(['A', 'B', 'C'], $c->map(fn ($item) => strtoupper($item))->all());

        $missing = collect(['ctype', 'filter', 'hash'])
            ->reject(fn ($ext) => in_array($ext, ['ctype', 'hash']));
        $this->assertEquals(['filter'], $missing->values()->all());
        $this->assertEquals('filter', $missing->join(', ', ', and '));

        $multiple = collect(['a', 'b', 'c'])->join(', ', ', and ');
        $this->assertEquals('a, b, and c', $multiple);

        $defaults = ['# DB_HOST=127.0.0.1', '# DB_PORT=3306'];
        $uncommented = collect($defaults)->map(fn ($d) => substr($d, 2))->all();
        $this->assertEquals(['DB_HOST=127.0.0.1', 'DB_PORT=3306'], $uncommented);

        $keys = collect(['sqlite' => 'SQLite', 'mysql' => 'MySQL'])->keys()->all();
        $this->assertEquals(['sqlite', 'mysql'], $keys);

        $first = collect(['sqlite' => 'SQLite', 'mysql' => 'MySQL'])->keys()->first();
        $this->assertEquals('sqlite', $first);

        $sorted = collect([
            'mysql' => ['MySQL', false],
            'sqlite' => ['SQLite', true],
        ])->sortBy(fn ($db) => $db[1] ? 0 : 1)->keys()->all();

        $this->assertEquals(['sqlite', 'mysql'], $sorted);
    }

    public function test_ensure_extensions_available_does_not_fail()
    {
        $command = new class extends NewCommand {
            public function testExtensions()
            {
                $input = new ArrayInput([]);
                $output = new BufferedOutput();
                $this->ensureExtensionsAreAvailable($input, $output);
                return true;
            }
        };

        $this->assertTrue($command->testExtensions());
    }
}
