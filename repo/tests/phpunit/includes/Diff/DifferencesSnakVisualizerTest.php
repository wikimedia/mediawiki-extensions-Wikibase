<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use MediaWikiTestCase;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Repo\Diff\DifferencesSnakVisualizer;

/**
 * @covers Wikibase\Repo\Diff\DifferencesSnakVisualizer
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseClaim
 *
 * @licence GNU GPL v2+
 * @author Adrian Heine < adrian.heine@wikimedia.de >
 */
class DifferencesSnakVisualizerTest extends MediaWikiTestCase {

	public function newSnakFormatter( $returnValue = '<i>SNAK</i>', $format = SnakFormatter::FORMAT_HTML ) {
		$instance = $this->getMock( 'Wikibase\Lib\SnakFormatter' );
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

	public function newEntityIdLabelFormatter() {
		$instance = $this
			->getMockBuilder( 'Wikibase\Lib\EntityIdLabelFormatter' )
			->disableOriginalConstructor()
			->getMock();

		$instance->expects( $this->any() )
			->method( 'format' )
			->will( $this->returnValue( '<a>PID</a>' ) );

		return $instance;
	}

	public function newDifferencesSnakVisualizer(){
		return new DifferencesSnakVisualizer(
			$this->newEntityIdLabelFormatter(),
			$this->newSnakFormatter( '<i>DETAILED SNAK</i>' ),
			$this->newSnakFormatter(),
			'en'
		);
	}

	public function testConstruction(){
		$instance = $this->newDifferencesSnakVisualizer();
		$this->assertInstanceOf( 'Wikibase\Repo\Diff\DifferencesSnakVisualizer', $instance );
	}

	public function testConstructionWithBadDetailsFormatter() {
		$this->setExpectedException( 'InvalidArgumentException' );
		new DifferencesSnakVisualizer(
			$this->newEntityIdLabelFormatter(),
			$this->newSnakFormatter( '', 'qwertyuiop' ),
			$this->newSnakFormatter(),
			'en'
		);
	}

	public function testConstructionWithBadTerseFormatter() {
		$this->setExpectedException( 'InvalidArgumentException' );
		new DifferencesSnakVisualizer(
			$this->newEntityIdLabelFormatter(),
			$this->newSnakFormatter(),
			$this->newSnakFormatter( '', 'qwertyuiop' ),
			'en'
		);
	}

	/**
	 * @dataProvider provideFormatSnak
	 */
	public function testFormatSnak( $snak, $expected ) {
		$snakVisualizer = $this->newDifferencesSnakVisualizer();
		$result = $snakVisualizer->formatSnak( $snak );
		$this->assertEquals($result, $expected );
	}

	public function provideFormatSnak() {
		$expected = '<a>PID</a>: <i>DETAILED SNAK</i>';
		return array(
			array( new PropertySomeValueSnak( new PropertyId( 'P1' ) ), $expected ),
			array( new PropertyNoValueSnak( new PropertyId( 'P1' ) ), $expected ),
			array( new PropertyValueSnak( new PropertyId( 'P1' ), new StringValue( '' ) ), $expected ),
			//array( null, '' ),
		);
	}

	/**
	 * @dataProvider provideFormatSnakDetails
	 */
	public function testFormatSnakDetails( $snak, $expected ) {
		$snakVisualizer = $this->newDifferencesSnakVisualizer();
		$result = $snakVisualizer->formatSnakDetails( $snak );
		$this->assertEquals($result, $expected );
	}

	public function provideFormatSnakDetails() {
		$expected = '<i>DETAILED SNAK</i>';
		return array(
			array( new PropertySomeValueSnak( new PropertyId( 'P1' ) ), $expected ),
			array( new PropertyNoValueSnak( new PropertyId( 'P1' ) ), $expected ),
			array( new PropertyValueSnak( new PropertyId( 'P1' ), new StringValue( '' ) ), $expected ),
			array( null, '' ),
		);
	}

	/**
	 * @dataProvider provideGetSnakValueHeader
	 */
	public function testGetSnakValueHeader( $snak, $expected ) {
		$snakVisualizer = $this->newDifferencesSnakVisualizer();
		$result = $snakVisualizer->getSnakValueHeader( $snak );
		$this->assertEquals($result, $expected );
	}

	public function provideGetSnakValueHeader() {
		$expected = 'property / <a>PID</a>: <i>SNAK</i>';
		return array(
			array( new PropertySomeValueSnak( new PropertyId( 'P1' ) ), $expected ),
			array( new PropertyNoValueSnak( new PropertyId( 'P1' ) ), $expected ),
			array( new PropertyValueSnak( new PropertyId( 'P1' ), new StringValue( '' ) ), $expected ),
			//array( null, '' ),
		);
	}

	/**
	 * @dataProvider provideGetSnakLabelHeader
	 */
	public function testGetSnakLabelHeader( $snak, $expected ) {
		$snakVisualizer = $this->newDifferencesSnakVisualizer();
		$result = $snakVisualizer->getSnakLabelHeader( $snak );
		$this->assertEquals($result, $expected );
	}

	public function provideGetSnakLabelHeader() {
		$expected  = 'property / <a>PID</a>';
		return array(
			array( new PropertySomeValueSnak( new PropertyId( 'P1' ) ), $expected ),
			array( new PropertyNoValueSnak( new PropertyId( 'P1' ) ), $expected ),
			array( new PropertyValueSnak( new PropertyId( 'P1' ), new StringValue( '' ) ), $expected ),
			array( null, 'property' ),
		);
	}
}
