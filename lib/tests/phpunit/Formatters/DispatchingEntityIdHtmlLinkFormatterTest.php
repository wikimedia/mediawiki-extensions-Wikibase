<?php

namespace Wikibase\Lib\Tests\Formatters;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\EntityIdHtmlLinkFormatter;
use Wikibase\Lib\Formatters\DispatchingEntityIdHtmlLinkFormatter;

/**
 * @covers \Wikibase\Lib\DispatchingEntityIdHtmlLinkFormatter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class DispatchingEntityIdHtmlLinkFormatterTest extends TestCase {

	public function testGivenFormatterMissing_UseDefaultFormatter() {
		$mockDefaultFormatter = $this->createMock(EntityIdHtmlLinkFormatter::class);
		$mockDefaultFormatter->expects($this->once())
			->method('formatEntityId');
		$formatter = new DispatchingEntityIdHtmlLinkFormatter([], $mockDefaultFormatter);
		$formatter->formatEntityId($this->createMock(entityId::class));

	}

	public function testGivenFormatterExists_FormatterUsed() {
		$mockFormatter = $this->createMock(EntityIdHtmlLinkFormatter::class);
		$mockFormatter->expects($this->once())
			->method('formatEntityId');
		$formatters = [ 'foo' => $mockFormatter ];

		$mockEntityId = $this->createMock(EntityId::class);
		$mockEntityId->expects($this->any())
			->method('getEntityType')
			->willReturn('foo');

		$mockDefaultFormatter = $this->createMock(EntityIdHtmlLinkFormatter::class);
		$formatter = new DispatchingEntityIdHtmlLinkFormatter($formatters, $mockDefaultFormatter);
		$formatter->formatEntityId($mockEntityId);
	}

	public function testGivenInvalidFormatter() {
		$formatters = [ 'foo' => 'aStringIsNotAFormatter' ];

		$mockEntityId = $this->createMock(EntityId::class);
		$mockEntityId->expects($this->any())
			->method('getEntityType')
			->willReturn('foo');

		$mockDefaultFormatter = $this->createMock(EntityIdHtmlLinkFormatter::class);

		$this->expectException(InvalidArgumentException::class);
		new DispatchingEntityIdHtmlLinkFormatter($formatters, $mockDefaultFormatter);
	}

}
