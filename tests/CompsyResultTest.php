<?php

declare (strict_types = 1);

namespace viavario\compsyclient\tests;

use PHPUnit\Framework\TestCase;
use viavario\compsyclient\CompsyResult;

class CompsyResultTest extends TestCase
{
    public function testConstructorSetsProperties(): void
    {
        $result = new CompsyResult('Dr. Jane Doe', 'https://example.com/jane', 'Active');

        $this->assertSame('Dr. Jane Doe', $result->name);
        $this->assertSame('https://example.com/jane', $result->detailUrl);
        $this->assertSame('Active', $result->status);
    }

    public function testIsActiveReturnsTrueForActiveStatus(): void
    {
        $result = new CompsyResult('Dr. Jane Doe', 'https://example.com', 'active');

        $this->assertTrue($result->isActive());
    }

    public function testIsActiveIsCaseInsensitive(): void
    {
        $result = new CompsyResult('Dr. Jane Doe', 'https://example.com', '  ACTIVE  ');

        $this->assertTrue($result->isActive());
    }

    public function testIsActiveReturnsFalseForInactiveStatus(): void
    {
        $result = new CompsyResult('Dr. Jane Doe', 'https://example.com', 'inactive');

        $this->assertFalse($result->isActive());
    }

    public function testToArrayReturnsCorrectStructure(): void
    {
        $result = new CompsyResult('Dr. Jane Doe', 'https://example.com/jane', 'active');

        $expected = [
            'name'       => 'Dr. Jane Doe',
            'detail_url' => 'https://example.com/jane',
            'status'     => 'active',
            'is_active'  => true,
        ];

        $this->assertSame($expected, $result->toArray());
    }

    public function testToArrayWithInactiveStatus(): void
    {
        $result = new CompsyResult('Dr. John Doe', 'https://example.com/john', 'suspended');

        $this->assertFalse($result->toArray()['is_active']);
    }
}
