<?php

namespace Wikibase\Lib\Test;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookupException;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\VocabularyUriFormatter;

/**
 * @covers Wikibase\Lib\VocabularyUriFormatter
 *
 * @group Wikibase
 * @group WikibaseLIb
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 * @author Thiemo Mättig
 */
class VocabularyUriFormatterTest extends PHPUnit_Framework_TestCase {

	public function unitProvider() {
		return array(
			'empty unit' => array( '', null ),
			'unit is 1' => array( '1', null ),
			'unit is "NotAUnit"' => array( 'NotAUnit', null ),
			'unit is bad id' => array( 'kittens', 'kittens' ),
			'unit has label' => array( 'Q7', 'LABEL:Q7' ),
			'unit has no label' => array( 'Q112233', 'Q112233' ),
			'unknown int' => array( '2', '2' ),
			'unknown URI' => array( 'http://www.wikidata.org/entity/Q200', 'http://www.wikidata.org/entity/Q200' ),
			'property id' => array( 'P7', 'LABEL:P7' ),
		);
	}

	/**
	 * @dataProvider unitProvider
	 */
	public function testFormat( $unit, $expected ) {
		$labelLookup = $this->getMock( LabelDescriptionLookup::class );
		$labelLookup->expects( $this->any() )
			->method( 'getLabel' )
			->will( $this->returnCallback( function( EntityId $id ) {
				if ( $id->getSerialization() === 'Q112233' ) {
					throw new LabelDescriptionLookupException( $id, 'No such label!' );
				}
				return new Term( 'en', 'LABEL:' . $id->getSerialization() );
			} ) );

		$idParser = new BasicEntityIdParser();

		$formatter = new VocabularyUriFormatter( $idParser, $labelLookup, array( 'NotAUnit' ) );

		$this->assertEquals( $expected, $formatter->format( $unit ) );
	}

}
