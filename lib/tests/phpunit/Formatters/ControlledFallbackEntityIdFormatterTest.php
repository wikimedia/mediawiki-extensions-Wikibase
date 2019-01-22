<?php

namespace Wikibase\Lib\Tests\Formatters;

use Liuggio\StatsdClient\Factory\StatsdDataFactoryInterface;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
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

	const DEFAULT_STATS_PREFIX = '';

	public function testUsesTargetFormatter() {

		$targetFormatter = $this->prophesize( EntityIdFormatter::class );
		$targetFormatter->formatEntityId( Argument::any() )->willReturn( 'some text' );
		$fallbackFormatter = $this->prophesize( EntityIdFormatter::class );

		$formatter = new ControlledFallbackEntityIdFormatter(
			$targetFormatter->reveal(),
			$fallbackFormatter->reveal(),
			$this->prophesize( StatsdDataFactoryInterface::class )->reveal(),
			self::DEFAULT_STATS_PREFIX
		);

		$this->assertEquals( 'some text', $formatter->formatEntityId( new ItemId( 'Q1' ) ) );
		$this->assertEquals( 'some text', $formatter->formatEntityId( new ItemId( 'Q2' ) ) );
		$fallbackFormatter->formatEntityId( Argument::any() )->shouldNotHaveBeenCalled();
	}

	public function testTargetFormatterThrowsAnExceptionWhileFormatting_UsesFallbackFormatter() {
		$givenItemId = new ItemId( 'Q1' );

		$targetFormatter = $this->prophesize( EntityIdFormatter::class );
		$targetFormatter->formatEntityId( $givenItemId )->willThrow( new \Exception() );
		$fallbackFormatter = $this->prophesize( EntityIdFormatter::class );
		$fallbackFormatter->formatEntityId( $givenItemId )->willReturn( 'fallback text' );

		$formatter = new ControlledFallbackEntityIdFormatter(
			$targetFormatter->reveal(),
			$fallbackFormatter->reveal(),
			$this->prophesize( StatsdDataFactoryInterface::class )->reveal(),
			self::DEFAULT_STATS_PREFIX
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
			$targetFormatter->reveal(),
			$fallbackFormatter->reveal(),
			$this->prophesize( StatsdDataFactoryInterface::class )->reveal(),
			self::DEFAULT_STATS_PREFIX
		);
		$formatter->setLogger( $logger->reveal() );
		$formatter->formatEntityId( $givenItemId );

		$logger->error(
			Argument::type( 'string' ),
			Argument::type( 'array' )
		)->shouldHaveBeenCalled();
	}

	public function testCallingTargetFormatter_CallIsTracked() {
		$givenItemId = new ItemId( 'Q1' );
		$statsPrefix = 'prefix.';

		$statsDataFactory = $this->prophesize( StatsdDataFactoryInterface::class );
		$formatter = new ControlledFallbackEntityIdFormatter(
			$this->prophesize( EntityIdFormatter::class )->reveal(),
			$this->prophesize( EntityIdFormatter::class )->reveal(),
			$statsDataFactory->reveal(),
			$statsPrefix
		);

		$formatter->formatEntityId( $givenItemId );

		$statsDataFactory->increment( 'prefix.targetFormatterCalled' )->shouldHaveBeenCalled();
	}

	public function testCallingTargetFormatterAndItThrowsAnException_FailureIsTracked() {
		$givenItemId = new ItemId( 'Q1' );
		$statsPrefix = 'prefix.';

		$targetFormatter = $this->prophesize( EntityIdFormatter::class );
		$targetFormatter->formatEntityId( $givenItemId )->willThrow( new \Exception() );
		$statsDataFactory = $this->prophesize( StatsdDataFactoryInterface::class );

		$formatter = new ControlledFallbackEntityIdFormatter(
			$targetFormatter->reveal(),
			$this->prophesize( EntityIdFormatter::class )->reveal(),
			$statsDataFactory->reveal(),
			$statsPrefix
		);

		$formatter->formatEntityId( $givenItemId );

		$statsDataFactory->increment( 'prefix.targetFormatterFailed' )->shouldHaveBeenCalled();
	}

}
