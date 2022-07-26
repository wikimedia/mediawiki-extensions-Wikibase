<?php

namespace Wikibase\Lib\Tests\Formatters;

use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\Lib\Formatters\DispatchingEntityIdHtmlLinkFormatter;

/**
 * @covers \Wikibase\Lib\DispatchingEntityIdHtmlLinkFormatter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class DispatchingEntityIdHtmlLinkFormatterTest extends TestCase {

	/**
	 * @var EntityIdFormatter|MockObject
	 */
	private $defaultFormatter;

	protected function setUp(): void {
		$this->defaultFormatter = $this->createMock( EntityIdFormatter::class );
	}

	public function testGivenFormatterMissing_UseDefaultFormatter() {
		$this->defaultFormatter->expects( $this->once() )
			->method( 'formatEntityId' );
		$formatter = new DispatchingEntityIdHtmlLinkFormatter( [], $this->defaultFormatter );
		$formatter->formatEntityId( $this->createMock( EntityId::class ) );
	}

	public function testGivenFormatterExists_FormatterUsed() {
		$formatter = $this->createMock( EntityIdFormatter::class );
		$formatter->expects( $this->once() )
			->method( 'formatEntityId' );
		$formatters = [ 'foo' => $formatter ];

		$mockEntityId = $this->createMock( EntityId::class );
		$mockEntityId->method( 'getEntityType' )
			->willReturn( 'foo' );

		$formatter = new DispatchingEntityIdHtmlLinkFormatter( $formatters, $this->defaultFormatter );
		$formatter->formatEntityId( $mockEntityId );
	}

	public function testGivenInvalidFormatter() {
		$formatters = [ 'foo' => 'aStringIsNotAFormatter' ];

		$this->expectException( InvalidArgumentException::class );
		new DispatchingEntityIdHtmlLinkFormatter( $formatters, $this->defaultFormatter );
	}

}
