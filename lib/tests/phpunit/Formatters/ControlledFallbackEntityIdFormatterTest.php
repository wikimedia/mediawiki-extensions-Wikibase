<?php

namespace Wikibase\Lib\Tests\Formatters;

use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\Lib\Formatters\ControlledFallbackEntityIdFormatter;
use PHPUnit\Framework\TestCase;

/**
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ControlledFallbackEntityIdFormatterTest extends TestCase {

	public function testGivenEntityIdIsGreaterThanMaxEntityId_UsesFallbackFormatter() {
		$maxEntityId = 1;
		$givenItemId = new ItemId( 'Q2' );

		$targetFormatter = $this->prophesize( EntityIdFormatter::class );
		$fallbackFormatter = $this->prophesize( EntityIdFormatter::class );
		$fallbackFormatter->formatEntityId( $givenItemId )->willReturn( 'some text' );

		$formatter = new ControlledFallbackEntityIdFormatter(
			$maxEntityId,
			$targetFormatter->reveal(),
			$fallbackFormatter->reveal()
		);
		$result = $formatter->formatEntityId( $givenItemId );

		$this->assertEquals( 'some text', $result );

		$targetFormatter->formatEntityId( $givenItemId )->shouldNotHaveBeenCalled();
	}

	public function testGivenEntityIdIsLessOrEqualThanMaxEntityId_UsesTargetFormatter() {
		$maxEntityId = 2;

		$targetFormatter = $this->prophesize( EntityIdFormatter::class );
		$targetFormatter->formatEntityId( Argument::any() )->willReturn( 'some text' );
		$fallbackFormatter = $this->prophesize( EntityIdFormatter::class );

		$formatter = new ControlledFallbackEntityIdFormatter(
			$maxEntityId,
			$targetFormatter->reveal(),
			$fallbackFormatter->reveal()
		);

		$this->assertEquals( 'some text', $formatter->formatEntityId( new ItemId( 'Q1' ) ) );
		$this->assertEquals( 'some text', $formatter->formatEntityId( new ItemId( 'Q2' ) ) );
		$fallbackFormatter->formatEntityId( Argument::any() )->shouldNotHaveBeenCalled();
	}

	public function testGivenEntityIdDoesNotImplementInt32EntityId_UsesFallbackFormatter() {
		$maxEntityId = 2;
		$givenEntityId = $this->someEntityId( "whatever" );

		$targetFormatter = $this->prophesize( EntityIdFormatter::class );
		$fallbackFormatter = $this->prophesize( EntityIdFormatter::class );
		$fallbackFormatter->formatEntityId( $givenEntityId )->willReturn( 'fallback text' );

		$formatter = new ControlledFallbackEntityIdFormatter(
			$maxEntityId,
			$targetFormatter->reveal(),
			$fallbackFormatter->reveal()
		);
		$result = $formatter->formatEntityId( $givenEntityId );

		$this->assertEquals( 'fallback text', $result );
		$targetFormatter->formatEntityId( $givenEntityId )->shouldNotHaveBeenCalled();
	}

	public function testTargetFormatterThrowsAnExceptionWhileFormatting_UsesFallbackFormatter() {
		$givenItemId = new ItemId( 'Q1' );

		$targetFormatter = $this->prophesize( EntityIdFormatter::class );
		$targetFormatter->formatEntityId( $givenItemId )->willThrow( new \Exception() );
		$fallbackFormatter = $this->prophesize( EntityIdFormatter::class );
		$fallbackFormatter->formatEntityId( $givenItemId )->willReturn( 'fallback text' );

		$formatter = new ControlledFallbackEntityIdFormatter(
			$maxEntityId = 100,
			$targetFormatter->reveal(),
			$fallbackFormatter->reveal()
		);
		$result = $formatter->formatEntityId( $givenItemId );

		$this->assertEquals( 'fallback text', $result );
	}

	public function testTargetFormatterThrowsAnExceptionWhileFormatting_ExceptionIsLogged() {
		$givenItemId = new ItemId( 'Q1' );

		$targetFormatter = $this->prophesize( EntityIdFormatter::class );
		$targetFormatter->formatEntityId( $givenItemId )->willThrow( new \Exception() );
		$fallbackFormatter = $this->prophesize( EntityIdFormatter::class );
		$logger = $this->prophesize( LoggerInterface::class );

		$formatter = new ControlledFallbackEntityIdFormatter(
			$maxEntityId = 100,
			$targetFormatter->reveal(),
			$fallbackFormatter->reveal()
		);
		$formatter->setLogger( $logger->reveal() );
		$formatter->formatEntityId( $givenItemId );

		$logger->critical(
			Argument::type( 'string' ),
			Argument::type( 'array' )
		)->shouldHaveBeenCalled();
	}

	private function someEntityId( $serialization ) {
		return new class( $serialization ) extends EntityId {

			public function serialize() {
			}

			public function unserialize( $serialized ) {
			}

			public function getEntityType() {
			}

		};
	}

}
