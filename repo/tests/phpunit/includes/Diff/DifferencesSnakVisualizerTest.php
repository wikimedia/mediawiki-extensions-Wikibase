<?php

namespace Wikibase\Repo\Tests\Diff;

use DataValues\StringValue;
use InvalidArgumentException;
use MediaWikiTestCase;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Repo\Diff\DifferencesSnakVisualizer;

/**
 * @covers Wikibase\Repo\Diff\DifferencesSnakVisualizer
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class DifferencesSnakVisualizerTest extends MediaWikiTestCase {

	/**
	 * @param string $returnValue
	 * @param string $format
	 *
	 * @return SnakFormatter
	 */
	public function newSnakFormatter( $returnValue = '<i>SNAK</i>', $format = SnakFormatter::FORMAT_HTML ) {
		$instance = $this->getMock( SnakFormatter::class );
		$instance->expects( $this->any() )
			->method( 'getFormat' )
			->will( $this->returnValue( $format ) );
		$instance->expects( $this->any() )
			->method( 'canFormatSnak' )
			->will( $this->returnValue( true ) );
		$instance->expects( $this->any() )
			->method( 'formatSnak' )
			->will( $this->returnValue( $returnValue ) );
		return $instance;
	}

	/**
	 * @return EntityIdFormatter
	 */
	public function newEntityIdLabelFormatter() {
		$instance = $this->getMock( EntityIdFormatter::class );

		$instance->expects( $this->any() )
			->method( 'formatEntityId' )
			->will( $this->returnValue( '<a>PID</a>' ) );

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
		$this->setExpectedException( InvalidArgumentException::class );
		new DifferencesSnakVisualizer(
			$this->newEntityIdLabelFormatter(),
			$this->newSnakFormatter( '', 'qwertyuiop' ),
			$this->newSnakFormatter(),
			'en'
		);
	}

	public function testConstructionWithBadTerseFormatter() {
		$this->setExpectedException( InvalidArgumentException::class );
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
			[ new PropertySomeValueSnak( new PropertyId( 'P1' ) ), $expected ],
			[ new PropertyNoValueSnak( new PropertyId( 'P1' ) ), $expected ],
			[ new PropertyValueSnak( new PropertyId( 'P1' ), new StringValue( '' ) ), $expected ],
			//array( null, '' ),
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
			[ new PropertySomeValueSnak( new PropertyId( 'P1' ) ), $expected ],
			[ new PropertyNoValueSnak( new PropertyId( 'P1' ) ), $expected ],
			[ new PropertyValueSnak( new PropertyId( 'P1' ), new StringValue( '' ) ), $expected ],
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
		$expected = 'property / <a>PID</a>: <i>SNAK</i>';
		return [
			[ new PropertySomeValueSnak( new PropertyId( 'P1' ) ), $expected ],
			[ new PropertyNoValueSnak( new PropertyId( 'P1' ) ), $expected ],
			[ new PropertyValueSnak( new PropertyId( 'P1' ), new StringValue( '' ) ), $expected ],
			//array( null, '' ),
		];
	}

	/**
	 * @dataProvider provideGetPropertyHeader
	 */
	public function testGetPropertyHeader( $snak, $expected ) {
		$snakVisualizer = $this->newDifferencesSnakVisualizer();
		$result = $snakVisualizer->getPropertyHeader( $snak );
		$this->assertEquals( $expected, $result );
	}

	public function provideGetPropertyHeader() {
		$expected = 'property / <a>PID</a>';
		return [
			[ new PropertySomeValueSnak( new PropertyId( 'P1' ) ), $expected ],
			[ new PropertyNoValueSnak( new PropertyId( 'P1' ) ), $expected ],
			[ new PropertyValueSnak( new PropertyId( 'P1' ), new StringValue( '' ) ), $expected ],
			[ null, 'property' ],
		];
	}

}
