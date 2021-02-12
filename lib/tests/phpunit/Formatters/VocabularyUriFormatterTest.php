<?php

namespace Wikibase\Lib\Tests\Formatters;

use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookupException;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\Formatters\VocabularyUriFormatter;

/**
 * @covers \Wikibase\Lib\Formatters\VocabularyUriFormatter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Thiemo Kreuz
 */
class VocabularyUriFormatterTest extends \PHPUnit\Framework\TestCase {

	public function unitProvider() {
		return [
			'empty unit' => [ '', null ],
			'unit is 1' => [ '1', null ],
			'unit is "NotAUnit"' => [ 'NotAUnit', null ],
			'unit is bad id' => [ 'kittens', 'kittens' ],
			'unit has label' => [ 'Q7', 'LABEL:Q7' ],
			'unit has no label' => [ 'Q112233', 'Q112233' ],
			'unknown int' => [ '2', '2' ],
			'unknown URI' => [ 'http://www.wikidata.org/entity/Q200', 'http://www.wikidata.org/entity/Q200' ],
			'property id' => [ 'P7', 'LABEL:P7' ],
		];
	}

	/**
	 * @dataProvider unitProvider
	 */
	public function testFormat( $unit, $expected ) {
		$labelLookup = $this->createMock( LabelDescriptionLookup::class );
		$labelLookup->method( 'getLabel' )
			->willReturnCallback( function( EntityId $id ) {
				if ( $id->getSerialization() === 'Q112233' ) {
					throw new LabelDescriptionLookupException( $id, 'No such label!' );
				}
				return new Term( 'en', 'LABEL:' . $id->getSerialization() );
			} );

		$formatter = new VocabularyUriFormatter(
			new BasicEntityIdParser(),
			$labelLookup,
			[ 'NotAUnit' ]
		);

		$this->assertEquals( $expected, $formatter->format( $unit ) );
	}

}
