<?php

declare( strict_types=1 );
namespace Wikibase\Repo\Tests\Dumpers;

use DataValues\StringValue;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lib\Serialization\CallbackFactory;
use Wikibase\Lib\Serialization\SerializationModifier;
use Wikibase\Repo\Dumpers\JsonDataTypeInjector;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Dumpers\JsonDataTypeInjector
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class JsonDataTypeInjectorTest extends TestCase {

	/**
	 * @dataProvider entityProvider
	 */
	public function testShouldInjectDatatypesInSerialization( $entityDocument ) {
		$serializer = WikibaseRepo::getCompactEntitySerializer();
		$serializedData = $serializer->serialize( $entityDocument );
		$injector = new JsonDataTypeInjector(
			new SerializationModifier(),
			new CallbackFactory(),
			$this->getMockPropertyDataTypeLookup(),
			WikibaseRepo::getEntityIdParser()
		);

		// should not be present before injection
		$this->assertArrayNotHasKey( 'datatype', $serializedData['claims']['P12'][0]['mainsnak'] );

		$serializedData = $injector->injectEntitySerializationWithDataTypes( $serializedData );

		// injects into mainsnak
		$this->assertArrayHasKey( 'datatype', $serializedData['claims']['P12'][0]['mainsnak'] );
		$this->assertSame( 'DtIdFor_P12', $serializedData['claims']['P12'][0]['mainsnak']['datatype'] );

		// injects into qualifiers
		$this->assertSame( 'DtIdFor_P12', $serializedData['claims']['P12'][1]['qualifiers']['P12'][0]['datatype'] );
		// injects into references
		$this->assertSame( 'DtIdFor_P12', $serializedData['claims']['P12'][1]['references'][0]['snaks']['P12'][0]['datatype'] );
	}

	public function entityProvider() {
		$snakList = new SnakList();
		$snakList->addSnak( new PropertySomeValueSnak( new NumericPropertyId( 'P12' ) ) );
		$snakList->addSnak( new PropertyValueSnak( new NumericPropertyId( 'P12' ), new StringValue( 'stringVal' ) ) );

		return [
			'basic usage' => [
				new Item(
					new ItemId( 'Q2' ),
					new Fingerprint(
						new TermList( [
							new Term( 'en', 'en-label' ),
							new Term( 'de', 'de-label' ),
						] ),
						new TermList( [
							new Term( 'fr', 'en-desc' ),
							new Term( 'de', 'de-desc' ),
						] ),
						new AliasGroupList( [
							new AliasGroup( 'en', [ 'ali1', 'ali2' ] ),
							new AliasGroup( 'dv', [ 'ali11', 'ali22' ] ),
						] )
					),
					new SiteLinkList( [
						new SiteLink( 'enwiki', 'Berlin' ),
						new SiteLink( 'dewiki', 'England', [ new ItemId( 'Q1' ) ] ),
					] ),
					new StatementList(
						new Statement(
							new PropertySomeValueSnak( new NumericPropertyId( 'P12' ) ),
							null,
							null,
							'GUID1'
						),
						new Statement(
							new PropertySomeValueSnak( new NumericPropertyId( 'P12' ) ),
							$snakList,
							new ReferenceList( [
								new Reference( [
									new PropertyValueSnak( new NumericPropertyId( 'P12' ), new StringValue( 'refSnakVal' ) ),
									new PropertyNoValueSnak( new NumericPropertyId( 'P12' ) ),
								] ),
							] ),
							'GUID2'
						)
					)
				),
			],
		];
	}

	/**
	 * @return PropertyDataTypeLookup
	 */
	private function getMockPropertyDataTypeLookup() {
		$mockDataTypeLookup = $this->createMock( PropertyDataTypeLookup::class );
		$mockDataTypeLookup->method( 'getDataTypeIdForProperty' )
			->willReturnCallback( function( PropertyId $id ) {
				return 'DtIdFor_' . $id->getSerialization();
			} );
		return $mockDataTypeLookup;
	}
}
