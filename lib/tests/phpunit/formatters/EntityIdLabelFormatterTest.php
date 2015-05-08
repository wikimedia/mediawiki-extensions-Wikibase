<?php

namespace Wikibase\Test;

use OutOfBoundsException;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\EntityIdLabelFormatter;

/**
 * @covers Wikibase\Lib\EntityIdLabelFormatter
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group WikibaseLib
 * @group Wikibase
 * @group EntityIdFormatterTest
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class EntityIdLabelFormatterTest extends PHPUnit_Framework_TestCase {

	/**
	 * @return array
	 */
	public function validProvider() {
		$argLists = array();

		$argLists[] = array( new ItemId( 'Q42' ), 'es', 'foo' );

		$argLists[] = array( new ItemId( 'Q9001' ), 'en', 'Q9001' );

		$argLists[] = array( new PropertyId( 'P9001' ), 'en', 'P9001' );

		$argLists['unresolved-redirect'] = array( new ItemId( 'Q23' ), 'en', 'Q23' );

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

	protected function getLabelDescriptionLookup( $languageCode ) {
		$labelDescriptionLookup = $this->getMockBuilder( 'Wikibase\Lib\Store\LabelDescriptionLookup' )
			->disableOriginalConstructor()
			->getMock();

		$labelDescriptionLookup->expects( $this->any() )
			->method( 'getLabel' )
			->will( $this->returnCallback( function( EntityId $entityId ) use ( $languageCode ) {
				if ( $entityId->getSerialization() === 'Q42' && $languageCode === 'es' ) {
					return new Term( 'es', 'foo' );
				} else {
					throw new OutOfBoundsException( 'Label not found' );
				}
			} ) );

		return $labelDescriptionLookup;
	}

}
