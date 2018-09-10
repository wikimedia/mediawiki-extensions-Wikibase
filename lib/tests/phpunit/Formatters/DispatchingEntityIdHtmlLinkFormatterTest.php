<?php

namespace Wikibase\Lib\Tests\Formatters;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
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

	use PHPUnit4And6Compat;

	/**
	 * @var EntityIdFormatter|MockObject
	 */
	private $defaultFormatter;

	public function setUp() {
		$this->defaultFormatter = $this->newMockFormatter();
	}

	public function testGivenFormatterMissing_UseDefaultFormatter() {
		$this->defaultFormatter->expects( $this->once() )
			->method( 'formatEntityId' );
		$formatter = new DispatchingEntityIdHtmlLinkFormatter( [], $this->defaultFormatter );
		$formatter->formatEntityId( $this->createMock( EntityId::class ) );
	}

	public function testGivenFormatterExists_FormatterUsed() {
		$formatter = $this->newMockFormatter();
		$formatter->expects( $this->once() )
			->method( 'formatEntityId' );
		$formatters = [ 'foo' => $formatter ];

		$mockEntityId = $this->createMock( EntityId::class );
		$mockEntityId->expects( $this->any() )
			->method( 'getEntityType' )
			->willReturn( 'foo' );

		$formatter = new DispatchingEntityIdHtmlLinkFormatter( $formatters, $this->defaultFormatter );
		$formatter->formatEntityId( $mockEntityId );
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testGivenInvalidFormatter() {
		$formatters = [ 'foo' => 'aStringIsNotAFormatter' ];

		new DispatchingEntityIdHtmlLinkFormatter( $formatters, $this->defaultFormatter );
	}

	private function newMockFormatter() {
		return $this->createMock( EntityIdFormatter::class );
	}

}
