<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use AssertionError;
use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;
use Wikibase\Repo\ValueParserFactory;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ValueParserFactoryTest extends ServiceWiringTestCase {

	public function testRegistersCustomParser() {
		$this->installMockDataTypeDefinitions( [
			'PT:custom' => [
				'parser-factory-callback' => $this->makeParserFactoryCallback(),
				'value-type' => 'string',
			],
		] );

		/** @var $valueParserFactory ValueParserFactory */
		$valueParserFactory = $this->getService( 'WikibaseRepo.ValueParserFactory' );

		$this->assertInstanceOf( ValueParserFactory::class, $valueParserFactory );
		$this->assertContains( 'custom', $valueParserFactory->getParserIds() );
	}

	public function testRegistersNullParser() {
		$this->installMockDataTypeDefinitions( [] );

		/** @var $valueParserFactory ValueParserFactory */
		$valueParserFactory = $this->getService( 'WikibaseRepo.ValueParserFactory' );

		$this->assertSame( [ 'null' ], $valueParserFactory->getParserIds() );
	}

	public function testRegistersLegacyEntityidParser() {
		$this->installMockDataTypeDefinitions( [
			'PT:wikibase-item' => [
				'parser-factory-callback' => $this->makeParserFactoryCallback(),
				'value-type' => 'wikibase-entityid',
			],
			'VT:wikibase-entityid' => [
				'parser-factory-callback' => $this->makeParserFactoryCallback(),
			],
		] );

		/** @var $valueParserFactory ValueParserFactory */
		$valueParserFactory = $this->getService( 'WikibaseRepo.ValueParserFactory' );

		$parserIds = $valueParserFactory->getParserIds();
		$this->assertContains( 'wikibase-item', $parserIds, 'registers correct parser' );
		$this->assertContains( 'wikibase-entityid', $parserIds, 'registers legacy parser' );
	}

	public function testRegistersLegacyGlobecoordinateParser() {
		$this->installMockDataTypeDefinitions( [
			'PT:globe-coordinate' => [
				'parser-factory-callback' => $this->makeParserFactoryCallback(),
				'value-type' => 'globecoordinate',
			],
			'VT:globecoordinate' => [
				'parser-factory-callback' => $this->makeParserFactoryCallback(),
			],
		] );

		/** @var $valueParserFactory ValueParserFactory */
		$valueParserFactory = $this->getService( 'WikibaseRepo.ValueParserFactory' );

		$parserIds = $valueParserFactory->getParserIds();
		$this->assertContains( 'globe-coordinate', $parserIds, 'registers correct parser' );
		$this->assertContains( 'globecoordinate', $parserIds, 'registers legacy parser' );
	}

	private function installMockDataTypeDefinitions( array $dataTypeDefinitions ): void {
		$this->mockService( 'WikibaseRepo.DataTypeDefinitions',
			new DataTypeDefinitions( $dataTypeDefinitions ) );
	}

	private function makeParserFactoryCallback(): callable {
		return function () {
			throw new AssertionError( 'Should never be called' );
		};
	}

}
