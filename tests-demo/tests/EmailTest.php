<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{
    public function testCanBeCreatedFromValidEmailAddress(): void
    {
        $this->assertInstanceOf(
            Email::class,
            Email::fromString('user@example.com')
        );
    }

    public function testCannotBeCreatedFromInvalidEmailAddress(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Email::fromString('invalid');
    }

    public function testCanBeUsedAsString(): void
    {
        $this->assertEquals(
            'user@example.com',
            Email::fromString('user@exampl.com')
        );
    }

    public function testAsIncomplete()
    {
        $this->assertTrue(true, 'This should already work.');

        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @requires extension mysqli
     * @requires PHP <= 5.3
     */
    public function testSkippedTest()
    {
        // Test requires the mysqli extension and PHP >= 5.3
    }
}
