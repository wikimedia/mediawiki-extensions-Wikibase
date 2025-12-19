<?php

namespace Wikibase\DataModel\Services\Tests\EntityId;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\EntityId\EntityIdLabelFormatter;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookupException;
use Wikibase\DataModel\Services\Lookup\LabelLookup;
use Wikibase\DataModel\Term\Term;

/**
 * @covers \Wikibase\DataModel\Services\EntityId\EntityIdLabelFormatter
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class EntityIdLabelFormatterTest extends TestCase {

	public static function validProvider(): iterable {
		yield [ new ItemId( 'Q42' ), 'es', 'foo' ];
		yield [ new ItemId( 'Q9001' ), 'en', 'Q9001' ];
		yield [ new NumericPropertyId( 'P9001' ), 'en', 'P9001' ];
		yield 'unresolved-redirect' => [ new ItemId( 'Q23' ), 'en', 'Q23' ];
	}

	/**
	 * @dataProvider validProvider
	 *
	 * @param EntityId $entityId
	 * @param string $languageCode
	 * @param string $expectedString
	 */
	public function testParseWithValidArguments( EntityId $entityId, $languageCode, $expectedString ) {
		$formatter = new EntityIdLabelFormatter( $this->getLabelLookup( $languageCode ) );

		$formattedValue = $formatter->formatEntityId( $entityId );

		$this->assertIsString( $formattedValue );
		$this->assertEquals( $expectedString, $formattedValue );
	}

	/**
	 * @param string $languageCode
	 *
	 * @return LabelLookup
	 */
	private function getLabelLookup( $languageCode ) {
		$labelLookup = $this->createMock( LabelLookup::class );

		$labelLookup->expects( $this->any() )
			->method( 'getLabel' )
			->willReturnCallback( static function( EntityId $id ) use ( $languageCode ) {
				if ( $id->getSerialization() === 'Q42' && $languageCode === 'es' ) {
					return new Term( 'es', 'foo' );
				} else {
					throw new LabelDescriptionLookupException( $id, 'Label not found' );
				}
			} );

		return $labelLookup;
	}

}
