<?php

namespace Wikibase\Lib\Test;

use OutOfBoundsException;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\EntityLabelUnitFormatter;

/**
 * @covers Wikibase\Lib\EntityLabelUnitFormatter
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group WikibaseLib
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class EntityLabelUnitFormatterTest extends PHPUnit_Framework_TestCase {

	public function provideApplyUnit() {
		return array(
			'empty unit' => array( '', '12345', '12345' ),
			'unit is 1' => array( '1', '12345', '12345' ),
			'unit is Q199' => array( 'https://www.wikidata.org/entity/Q199', '12345', '12345' ),
			'unit is bad id' => array( 'kittens', '12345', '12345 kittens' ),
			'unit has label' => array( 'Q7', '12345', '12345 LABEL:Q7' ),
			'unit has no label' => array( 'Q112233', '12345', '12345 Q112233' ),
		);
	}

	/**
	 * @dataProvider provideApplyUnit
	 */
	public function testApplyUnit( $unit, $number, $expected ) {
		$labelLookup = $this->getMock( 'Wikibase\Lib\Store\LabelDescriptionLookup' );
		$labelLookup->expects( $this->any() )
			->method( 'getLabel' )
			->will( $this->returnCallback( function( EntityId $id ) {
				if ( $id->getNumericId() > 1000 ) {
					throw new OutOfBoundsException( 'No such label!' );
				}
				return 'LABEL:' . $id->getSerialization();
			} ) );

		$idParser = new BasicEntityIdParser();

		$formatter = new EntityLabelUnitFormatter( $idParser, $labelLookup );
		$this->assertEquals( $expected, $formatter->applyUnit( $unit, $number ) );
	}

}
