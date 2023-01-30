<?php

namespace Wikibase\Client\Tests\Integration\DataAccess;

use DataValues\Geo\Values\GlobeCoordinateValue;
use DataValues\Geo\Values\LatLongValue;
use DataValues\StringValue;
use Wikibase\Client\Tests\Mocks\MockClientStore;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Lib\Tests\MockRepository;

/**
 * Helper class for Lua integration tests.
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class WikibaseDataAccessTestItemSetUpHelper {

	/**
	 * @var MockRepository
	 */
	private $siteLinkLookup;

	public function __construct( MockClientStore $clientStore ) {
		$this->siteLinkLookup = $clientStore->getSiteLinkLookup();
	}

	/**
	 * Sets up the test data.
	 */
	public function setUp(): void {
		$siteLink = new SiteLink(
			WikibaseClient::getSettings()->getSetting( 'siteGlobalID' ),
			'WikibaseClientDataAccessTest'
		);

		if ( $this->siteLinkLookup->getItemIdForSiteLink( $siteLink ) ) {
			// Already set up for this MockRepository
			return;
		}

		$stringProperty = $this->getTestProperty( new NumericPropertyId( 'P342' ), 'string', 'LuaTestStringProperty' );
		$itemProperty = $this->getTestProperty( new NumericPropertyId( 'P456' ), 'wikibase-item', 'LuaTestItemProperty' );
		$globeCoordinateProperty = $this->getTestProperty(
			new NumericPropertyId( 'P625' ),
			'globe-coordinate',
			'location'
		);

		$stringSnak = new PropertyValueSnak(
			$stringProperty->getId(),
			new StringValue( 'Lua :)' )
		);

		$statement1 = $this->getTestStatement( $stringSnak );
		$statement1->setRank( Statement::RANK_PREFERRED );

		$qualifierSnak1 = new PropertyValueSnak(
			new NumericPropertyId( 'P342' ),
			new StringValue( 'A qualifier Snak' )
		);
		$qualifierSnak2 = new PropertyValueSnak(
			new NumericPropertyId( 'P342' ),
			new StringValue( 'Moar qualifiers' )
		);
		$referenceSnak = new PropertyValueSnak(
			new NumericPropertyId( 'P342' ),
			new StringValue( 'A reference' )
		);

		$statement1->setQualifiers(
			new SnakList( [ $qualifierSnak1, $qualifierSnak2 ] )
		);

		$statement1->addNewReference( $referenceSnak );

		$stringProperty->getStatements()->addStatement( $statement1 );
		$this->siteLinkLookup->putEntity( $stringProperty );
		$this->siteLinkLookup->putEntity( $itemProperty );
		$this->siteLinkLookup->putEntity( $globeCoordinateProperty );

		$stringSnak2 = new PropertyValueSnak(
			$stringProperty->getId(),
			new StringValue( 'Lua is clearly superior to the parser function' )
		);

		$statement2 = $this->getTestStatement( $stringSnak2 );
		$statement2->setRank( Statement::RANK_NORMAL );

		$itemSnak = new PropertyValueSnak(
			$itemProperty->getId(),
			new EntityIdValue( new ItemId( 'Q885588' ) )
		);

		$statement3 = $this->getTestStatement( $itemSnak );
		$statement3->setRank( Statement::RANK_NORMAL );

		$globeCoordinateSnak = new PropertyValueSnak(
			$globeCoordinateProperty->getId(),
			new GlobeCoordinateValue( new LatLongValue( 1, 1 ) )
		);

		$globeCoordinateStatement = $this->getTestStatement( $globeCoordinateSnak );
		$globeCoordinateStatement->setRank( Statement::RANK_NORMAL );

		$siteLinks = [ $siteLink ];
		$siteLinks[] = new SiteLink(
			'fooSiteId',
			'FooBarFoo',
			[ new ItemId( 'Q10001' ), new ItemId( 'Q10002' ) ]
		);

		$labels = [
			'de' => 'Lua-Test-Datenobjekt',
			'en' => 'Lua Test Item',
		];

		$this->createTestItem( new ItemId( 'Q32487' ), $labels, [ $statement1, $statement2 ], $siteLinks );

		$this->createTestItem( new ItemId( 'Q32488' ), [], [ $statement1, $statement3 ], [] );

		$this->createTestItem(
			new ItemId( 'Q32489' ),
			[],
			[ $statement1, $statement1, $globeCoordinateStatement ],
			[]
		);

		// Create another test item to test arbitrary access
		$this->createTestItem( new ItemId( 'Q199024' ), [ 'de' => 'Arbitrary access \o/' ] );

		$this->createTestItem( new ItemId( 'Q885588' ), [ 'ku-latn' => 'Pisîk' ] );
	}

	/**
	 * @param NumericPropertyId $id
	 * @param string $dataTypeId
	 * @param string $label
	 *
	 * @return Property
	 */
	private function getTestProperty( NumericPropertyId $id, $dataTypeId, $label ) {
		$property = Property::newFromType( $dataTypeId );
		$property->setId( $id );
		$property->setLabel( 'de', $label );

		return $property;
	}

	/**
	 * @param ItemId $id
	 * @param string[] $labels
	 * @param Statement[]|null $statements
	 * @param SiteLink[]|null $siteLinks
	 *
	 * @return Item
	 */
	private function createTestItem( ItemId $id, array $labels, array $statements = null, array $siteLinks = null ) {
		$item = new Item( $id );
		$item->setDescription( 'de', 'Description of ' . $id->getSerialization() );

		foreach ( $labels as $lang => $label ) {
			$item->setLabel( $lang, $label );
		}

		if ( $statements !== null ) {
			$item->setStatements( new StatementList( ...$statements ) );
		}

		if ( $siteLinks !== null ) {
			$item->setSiteLinkList( new SiteLinkList( $siteLinks ) );
		}

		$this->siteLinkLookup->putEntity( $item );

		return $item;
	}

	/**
	 * @param Snak $mainSnak
	 *
	 * @return Statement
	 */
	private function getTestStatement( Snak $mainSnak ) {
		$statement = new Statement( $mainSnak );
		$statement->setGuid( uniqid( 'kittens', true ) );

		return $statement;
	}

}
