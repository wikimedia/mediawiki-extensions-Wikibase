<?php

namespace Wikibase\DataModel\Services\Tests\EntityId;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\EntityId\EntityIdLabelFormatter;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookupException;
use Wikibase\DataModel\Term\Term;

/**
 * @covers Wikibase\DataModel\Services\EntityId\EntityIdLabelFormatter
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class EntityIdLabelFormatterTest extends PHPUnit_Framework_TestCase {

	/**
	 * @return array
	 */
	public function validProvider() {
		$argLists = [];

		$argLists[] = [ new ItemId( 'Q42' ), 'es', 'foo' ];

		$argLists[] = [ new ItemId( 'Q9001' ), 'en', 'Q9001' ];

		$argLists[] = [ new PropertyId( 'P9001' ), 'en', 'P9001' ];

		$argLists['unresolved-redirect'] = [ new ItemId( 'Q23' ), 'en', 'Q23' ];

		return $argLists;
	}

	/**
	 * @dataProvider validProvider
	 *
	 * @param EntityId $entityId
	 * @param string $languageCode
	 * @param string $expectedString
	 */
	public function testParseWithValidArguments( EntityId $entityId, $languageCode, $expectedString ) {
		$labelDescriptionLookup = $this->getLabelDescriptionLookup( $languageCode );
		$formatter = new EntityIdLabelFormatter( $labelDescriptionLookup );

		$formattedValue = $formatter->formatEntityId( $entityId );

		$this->assertInternalType( 'string', $formattedValue );
		$this->assertEquals( $expectedString, $formattedValue );
	}

	/**
	 * @param string $languageCode
	 *
	 * @return LabelDescriptionLookup
	 */
	private function getLabelDescriptionLookup( $languageCode ) {
		$labelDescriptionLookup = $this->getMockBuilder( 'Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup' )
			->disableOriginalConstructor()
			->getMock();

		$labelDescriptionLookup->expects( $this->any() )
			->method( 'getLabel' )
			->will( $this->returnCallback( function( EntityId $id ) use ( $languageCode ) {
				if ( $id->getSerialization() === 'Q42' && $languageCode === 'es' ) {
					return new Term( 'es', 'foo' );
				} else {
					throw new LabelDescriptionLookupException( $id, 'Label not found' );
				}
			} ) );

		return $labelDescriptionLookup;
	}

}
