<?php

namespace Wikibase\Repo\Tests\Diff;

use DataValues\StringValue;
use InvalidArgumentException;
use MediaWikiIntegrationTestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\Formatters\SnakFormatter;
use Wikibase\Repo\Diff\DifferencesSnakVisualizer;

/**
 * @covers \Wikibase\Repo\Diff\DifferencesSnakVisualizer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class DifferencesSnakVisualizerTest extends MediaWikiIntegrationTestCase {

	/**
	 * @param string $returnValue
	 * @param string $format
	 *
	 * @return SnakFormatter
	 */
	public function newSnakFormatter( $returnValue = '<i>SNAK</i>', $format = SnakFormatter::FORMAT_HTML ) {
		$instance = $this->createMock( SnakFormatter::class );
		$instance->method( 'getFormat' )
			->willReturn( $format );
		$instance->method( 'formatSnak' )
			->willReturn( $returnValue );
		return $instance;
	}

	/**
	 * @return EntityIdFormatter
	 */
	public function newEntityIdLabelFormatter() {
		$instance = $this->createMock( EntityIdFormatter::class );

		$instance->method( 'formatEntityId' )
			->willReturn( '<a>PID</a>' );

		return $instance;
	}

	public function newDifferencesSnakVisualizer() {
		return new DifferencesSnakVisualizer(
			$this->newEntityIdLabelFormatter(),
			$this->newSnakFormatter( '<i>DETAILED SNAK</i>' ),
			$this->newSnakFormatter(),
			'en'
		);
	}

	public function testConstruction() {
		$instance = $this->newDifferencesSnakVisualizer();
		$this->assertInstanceOf( DifferencesSnakVisualizer::class, $instance );
	}

	public function testConstructionWithBadDetailsFormatter() {
		$this->expectException( InvalidArgumentException::class );
		new DifferencesSnakVisualizer(
			$this->newEntityIdLabelFormatter(),
			$this->newSnakFormatter( '', 'qwertyuiop' ),
			$this->newSnakFormatter(),
			'en'
		);
	}

	public function testConstructionWithBadTerseFormatter() {
		$this->expectException( InvalidArgumentException::class );
		new DifferencesSnakVisualizer(
			$this->newEntityIdLabelFormatter(),
			$this->newSnakFormatter(),
			$this->newSnakFormatter( '', 'qwertyuiop' ),
			'en'
		);
	}

	/**
	 * @dataProvider provideGetPropertyAndDetailedValue
	 */
	public function testGetPropertyAndDetailedValue( $snak, $expected ) {
		$snakVisualizer = $this->newDifferencesSnakVisualizer();
		$result = $snakVisualizer->getPropertyAndDetailedValue( $snak );
		$this->assertEquals( $expected, $result );
	}

	public function provideGetPropertyAndDetailedValue() {
		$expected = '<a>PID</a>: <i>DETAILED SNAK</i>';
		return [
			[ new PropertySomeValueSnak( new NumericPropertyId( 'P1' ) ), $expected ],
			[ new PropertyNoValueSnak( new NumericPropertyId( 'P1' ) ), $expected ],
			[ new PropertyValueSnak( new NumericPropertyId( 'P1' ), new StringValue( '' ) ), $expected ],
		];
	}

	/**
	 * @dataProvider provideGetDetailedValue
	 */
	public function testGetDetailedValue( $snak, $expected ) {
		$snakVisualizer = $this->newDifferencesSnakVisualizer();
		$result = $snakVisualizer->getDetailedValue( $snak );
		$this->assertEquals( $expected, $result );
	}

	public function provideGetDetailedValue() {
		$expected = '<i>DETAILED SNAK</i>';
		return [
			[ new PropertySomeValueSnak( new NumericPropertyId( 'P1' ) ), $expected ],
			[ new PropertyNoValueSnak( new NumericPropertyId( 'P1' ) ), $expected ],
			[ new PropertyValueSnak( new NumericPropertyId( 'P1' ), new StringValue( '' ) ), $expected ],
			[ null, '' ],
		];
	}

	/**
	 * @dataProvider provideGetPropertyAndValueHeader
	 */
	public function testGetPropertyAndValueHeader( $snak, $expected ) {
		$snakVisualizer = $this->newDifferencesSnakVisualizer();
		$result = $snakVisualizer->getPropertyAndValueHeader( $snak );
		$this->assertEquals( $expected, $result );
	}

	public function provideGetPropertyAndValueHeader() {
		$expected = 'Property / <a>PID</a>: <i>SNAK</i>';
		return [
			[ new PropertySomeValueSnak( new NumericPropertyId( 'P1' ) ), $expected ],
			[ new PropertyNoValueSnak( new NumericPropertyId( 'P1' ) ), $expected ],
			[ new PropertyValueSnak( new NumericPropertyId( 'P1' ), new StringValue( '' ) ), $expected ],
		];
	}

	/**
	 * @dataProvider provideGetPropertyHeader
	 */
	public function testGetPropertyHeader( ?Snak $snak, $expected ) {
		$snakVisualizer = $this->newDifferencesSnakVisualizer();
		$result = $snakVisualizer->getPropertyHeader( $snak );
		$this->assertEquals( $expected, $result );
	}

	public function provideGetPropertyHeader() {
		$expected = 'Property / <a>PID</a>';
		return [
			[ new PropertySomeValueSnak( new NumericPropertyId( 'P1' ) ), $expected ],
			[ new PropertyNoValueSnak( new NumericPropertyId( 'P1' ) ), $expected ],
			[ new PropertyValueSnak( new NumericPropertyId( 'P1' ), new StringValue( '' ) ), $expected ],
			[ null, 'Property' ],
			[ null, 'Property' ],
		];
	}

}
