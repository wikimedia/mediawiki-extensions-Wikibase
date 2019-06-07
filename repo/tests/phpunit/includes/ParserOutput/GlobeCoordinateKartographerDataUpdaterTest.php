<?php

namespace Wikibase\Repo\Tests\ParserOutput;

use DataValues\Geo\Values\GlobeCoordinateValue;
use DataValues\Geo\Values\LatLongValue;
use DataValues\StringValue;
use Language;
use ParserOutput;
use PHPUnit4And6Compat;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Lib\Formatters\CachingKartographerEmbeddingHandler;
use Wikibase\Repo\ParserOutput\GlobeCoordinateKartographerDataUpdater;

/**
 * @covers \Wikibase\Repo\ParserOutput\GlobeCoordinateKartographerDataUpdater
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class GlobeCoordinateKartographerDataUpdaterTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	public function testUpdateParserOutput() {
		$dataUpdater = new GlobeCoordinateKartographerDataUpdater(
			$this->newCachingKartographerEmbeddingHandler()
		);

		foreach ( $this->getStatements() as $statement ) {
			$dataUpdater->processStatement( $statement );
		}

		$parserOutput = new ParserOutput();
		$parserOutput->addJsConfigVars( 'wgUserLanguage', 'qqx' );
		$parserOutput->addModules( [ 'wikibase' ] );

		$expected = $this->getKartographerParserOutput();
		$dataUpdater->updateParserOutput( $parserOutput );

		$this->assertSame(
			$expected->getProperty( 'kartographer' ),
			$parserOutput->getProperty( 'kartographer' )
		);
		$this->assertSame(
			$expected->getProperty( 'kartographer_links' ),
			$parserOutput->getProperty( 'kartographer_links' )
		);
		$this->assertSame(
			$expected->getProperty( 'kartographer_frames' ),
			$parserOutput->getProperty( 'kartographer_frames' )
		);
		$this->assertEquals(
			[ 'wikibase', 'kartographer-rl-module1', 'javascript-stuffs' ],
			$parserOutput->getModules()
		);
		$this->assertEquals(
			[
				'wgUserLanguage' => 'qqx',
				'wgKartographerMapServer' => 'https://maps.wikimedia.org'
			],
			$parserOutput->getJsConfigVars()
		);
	}

	/**
	 * @return StatementList
	 */
	private function getStatements() {
		$statements = new StatementList();
		$statements->addNewStatement(
			new PropertyValueSnak( new PropertyId( 'P42' ), new StringValue( 'Strings should be ignored' ) )
		);
		$statements->addNewStatement(
			new PropertyNoValueSnak( new PropertyId( 'P42' ) )
		);
		$statements->addNewStatement(
			new PropertyValueSnak( new PropertyId( 'P123' ), $this->newCoordinateValue( 12, 34 ) )
		);
		$statements->addNewStatement(
			new PropertyValueSnak( new PropertyId( 'P123' ), $this->newCoordinateValue( 50, 50 ) )
		);

		return $statements;
	}

	/**
	 * @return CachingKartographerEmbeddingHandler
	 */
	private function newCachingKartographerEmbeddingHandler() {
		$handler = $this->getMockBuilder( CachingKartographerEmbeddingHandler::class )
			->disableOriginalConstructor()
			->getMock();

		$handler->expects( $this->once() )
			->method( 'getParserOutput' )
			->will( $this->returnCallback(
				function( array $values, Language $language ) {
					$this->assertContainsOnlyInstancesOf( GlobeCoordinateValue::class, $values );
					$this->assertSame( 'qqx', $language->getCode() );

					return $this->getKartographerParserOutput();
				}
			) );

		return $handler;
	}

	/**
	 * @return ParserOutput
	 */
	private function getKartographerParserOutput() {
		$parserOutput = new ParserOutput();
		$parserOutput->addModules( [ 'kartographer-rl-module1', 'javascript-stuffs' ] );
		$parserOutput->setProperty( 'kartographer', [ [ 'whatever' ] ] );
		$parserOutput->setProperty( 'kartographer_links', [ [ 34 ] ] );
		$parserOutput->setProperty( 'kartographer_frames', [ [ 'blah' ] ] );
		$parserOutput->addJsConfigVars( 'wgKartographerMapServer', 'https://maps.wikimedia.org' );

		return $parserOutput;
	}

	/**
	 * @param int $lat
	 * @param int $long
	 * @return GlobeCoordinateValue
	 */
	private function newCoordinateValue( $lat, $long ) {
		return new GlobeCoordinateValue(
			new LatLongValue( $lat, $long ),
			1,
			GlobeCoordinateValue::GLOBE_EARTH
		);
	}

}
