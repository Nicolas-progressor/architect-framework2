<?php

declare(strict_types=1);

namespace Tests\Services\Console;

use Architect\Console\Input;
use PHPUnit\Framework\TestCase;

class InputTest extends TestCase
{
    public function testEmptyArgv(): void
    {
        $input = new Input(['script.php']);
        $this->assertSame('', $input->getCommand());
        $this->assertSame([], $input->getArguments());
        $this->assertSame([], $input->getOptions());
    }

    public function testCommandOnly(): void
    {
        $input = new Input(['script.php', 'serve']);
        $this->assertSame('serve', $input->getCommand());
        $this->assertSame([], $input->getArguments());
    }

    public function testPositionalArguments(): void
    {
        $input = new Input(['script.php', 'migrate', 'users', 'posts']);
        $this->assertSame('migrate', $input->getCommand());
        $this->assertSame(['users', 'posts'], $input->getArguments());
    }

    public function testGetArgument(): void
    {
        $input = new Input(['script.php', 'cmd', 'first', 'second']);
        $this->assertSame('first', $input->getArgument(0));
        $this->assertSame('second', $input->getArgument(1));
        $this->assertNull($input->getArgument(5));
        $this->assertSame('default', $input->getArgument(5, 'default'));
    }

    public function testLongOption(): void
    {
        $input = new Input(['script.php', 'cmd', '--verbose']);
        $this->assertTrue($input->hasOption('verbose'));
        $this->assertTrue($input->getOption('verbose'));
    }

    public function testLongOptionWithValue(): void
    {
        $input = new Input(['script.php', 'cmd', '--path', '/usr/local']);
        $this->assertSame('/usr/local', $input->getOption('path'));
    }

    public function testLongOptionEquals(): void
    {
        $input = new Input(['script.php', 'cmd', '--name=test']);
        $this->assertSame('test', $input->getOption('name'));
    }

    public function testShortOption(): void
    {
        $input = new Input(['script.php', 'cmd', '-v']);
        $this->assertTrue($input->hasOption('v'));
    }

    public function testShortOptionWithValue(): void
    {
        $input = new Input(['script.php', 'cmd', '-n', '42']);
        $this->assertSame('42', $input->getOption('n'));
    }

    public function testMixedArgumentsAndOptions(): void
    {
        $input = new Input(['script.php', 'cmd', 'arg1', '--flag', '-f']);
        $this->assertSame(['arg1'], $input->getArguments());
        $this->assertTrue($input->hasOption('flag'));
        $this->assertTrue($input->hasOption('f'));
    }

    public function testGetOptions(): void
    {
        $input = new Input(['script.php', 'cmd', '--a=1', '--b=2']);
        $options = $input->getOptions();
        $this->assertSame('1', $options['a']);
        $this->assertSame('2', $options['b']);
    }

    public function testGetTokens(): void
    {
        $input = new Input(['script.php', 'cmd', 'arg', '--opt']);
        $this->assertSame(['cmd', 'arg', '--opt'], $input->getTokens());
    }

    public function testIsOption(): void
    {
        $input = new Input(['script.php', 'cmd', '--flag', '--val=true', '--nope=false']);
        $this->assertTrue($input->isOption('flag'));
        $this->assertTrue($input->isOption('val'));
        $this->assertFalse($input->isOption('nope'));
        $this->assertFalse($input->isOption('missing'));
    }

    public function testIsOptionStringValues(): void
    {
        $input = new Input(['script.php', 'cmd', '--a=1', '--b=yes', '--c=true', '--d=on']);
        $this->assertTrue($input->isOption('a'));
        $this->assertTrue($input->isOption('b'));
        $this->assertTrue($input->isOption('c'));
        $this->assertTrue($input->isOption('d'));
    }

    public function testIsHelp(): void
    {
        $input = new Input(['script.php', 'cmd', '--help']);
        $this->assertTrue($input->isHelp());

        $input2 = new Input(['script.php', 'cmd', '-h']);
        $this->assertTrue($input2->isHelp());

        $input3 = new Input(['script.php', 'cmd']);
        $this->assertFalse($input3->isHelp());
    }

    public function testIsVerbose(): void
    {
        $input = new Input(['script.php', 'cmd', '--verbose']);
        $this->assertTrue($input->isVerbose());

        $input2 = new Input(['script.php', 'cmd', '-v']);
        $this->assertTrue($input2->isVerbose());
    }

    public function testIsQuiet(): void
    {
        $input = new Input(['script.php', 'cmd', '--quiet']);
        $this->assertTrue($input->isQuiet());

        $input2 = new Input(['script.php', 'cmd', '-q']);
        $this->assertTrue($input2->isQuiet());
    }

    public function testOptionNotValue(): void
    {
        $input = new Input(['script.php', 'cmd', '--no-flag']);
        $this->assertFalse($input->hasOption('flag'));
        $this->assertTrue($input->hasOption('no-flag'));
    }

    public function testDefaultOption(): void
    {
        $input = new Input(['script.php', 'cmd']);
        $this->assertNull($input->getOption('missing'));
        $this->assertSame('fallback', $input->getOption('missing', 'fallback'));
    }
}
