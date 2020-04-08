<?php

namespace Wikibase\Client\Tests\Unit\DataAccess;

use Language;
use MessageLocalizer;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use TestLogger;
use Wikibase\Client\DataAccess\DataAccessSnakFormatterFactory;
use Wikibase\Client\DataAccess\ReferenceFormatterFactory;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\Lib\Formatters\Reference\WellKnownReferenceProperties;
use Wikibase\Lib\Formatters\SnakFormatter;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Wikibase\Client\DataAccess\ReferenceFormatterFactory
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseDataAccess
 *
 * @license GPL-2.0-or-later
 */
class ReferenceFormatterFactoryTest extends TestCase {

	public function testNewDataBridgeReferenceFormatter() {
		$messageLocalizer = $this->createMock( MessageLocalizer::class );
		$language = $this->createMock( Language::class );
		$usageAccumulator = $this->createMock( UsageAccumulator::class );
		$snakFormatter = $this->createMock( SnakFormatter::class );
		$snakFormatterFactory = $this->createMock( DataAccessSnakFormatterFactory::class );
		$snakFormatterFactory->method( 'newWikitextSnakFormatter' )
			->with(
				$this->identicalTo( $language ),
				$this->identicalTo( $usageAccumulator ),
				$this->identicalTo( DataAccessSnakFormatterFactory::TYPE_RICH_WIKITEXT )
			)
			->willReturn( $snakFormatter );
		$properties = $this->createMock( WellKnownReferenceProperties::class );

		$referenceFormatterFactory = new ReferenceFormatterFactory(
			$snakFormatterFactory,
			$properties
		);
		$referenceFormatter = $referenceFormatterFactory->newDataBridgeReferenceFormatter(
			$messageLocalizer,
			$language,
			$usageAccumulator
		);

		$wrapper = TestingAccessWrapper::newFromObject( $referenceFormatter );
		$this->assertSame( $snakFormatter, $wrapper->snakFormatter );
		$this->assertSame( $properties, $wrapper->properties );
		$this->assertSame( $messageLocalizer, $wrapper->messageLocalizer );
	}

	public function testLogIfPropertiesEmpty_logsForEmptyProperties() {
		$snakFormatterFactory = $this->createMock( DataAccessSnakFormatterFactory::class );
		$properties = WellKnownReferenceProperties::newFromArray( [] );
		$logger = new TestLogger( true );
		$referenceFormatterFactory = new ReferenceFormatterFactory(
			$snakFormatterFactory,
			$properties,
			$logger
		);

		TestingAccessWrapper::newFromObject( $referenceFormatterFactory )->logIfPropertiesEmpty();

		$buffer = $logger->getBuffer();
		$this->assertCount( 1, $buffer );
		$this->assertSame( LogLevel::INFO, $buffer[0][0] );
	}

	public function testLogIfPropertiesEmpty_doesNotLogForPartialProperties() {
		$snakFormatterFactory = $this->createMock( DataAccessSnakFormatterFactory::class );
		$properties = WellKnownReferenceProperties::newFromArray( [
			'referenceUrl' => 'P1',
		] );
		$logger = new TestLogger( true );
		$referenceFormatterFactory = new ReferenceFormatterFactory(
			$snakFormatterFactory,
			$properties,
			$logger
		);

		TestingAccessWrapper::newFromObject( $referenceFormatterFactory )->logIfPropertiesEmpty();

		$this->assertSame( [], $logger->getBuffer() );
	}

}
