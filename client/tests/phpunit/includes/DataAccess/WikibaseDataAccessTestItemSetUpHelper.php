<?php

namespace Wikibase\Client\Tests\DataAccess;

use DataValues\StringValue;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Test\MockClientStore;
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
	public function setUp() {
		$siteLink = new SiteLink(
			WikibaseClient::getDefaultInstance()->getSettings()->getSetting( 'siteGlobalID' ),
			'WikibaseClientDataAccessTest'
		);

		if ( $this->siteLinkLookup->getItemIdForSiteLink( $siteLink ) ) {
			// Already set up for this MockRepository
			return;
		}

		$stringProperty = $this->getTestProperty( new PropertyId( 'P342' ), 'string', 'LuaTestStringProperty' );
		$itemProperty = $this->getTestProperty( new PropertyId( 'P456' ), 'wikibase-item', 'LuaTestItemProperty' );

		$stringSnak = new PropertyValueSnak(
			$stringProperty->getId(),
			new StringValue( 'Lua :)' )
		);

		$statement1 = $this->getTestStatement( $stringSnak );
		$statement1->setRank( Statement::RANK_PREFERRED );

		$qualifierSnak1 = new PropertyValueSnak(
			new PropertyId( 'P342' ),
			new StringValue( 'A qualifier Snak' )
		);
		$qualifierSnak2 = new PropertyValueSnak(
			new PropertyId( 'P342' ),
			new StringValue( 'Moar qualifiers' )
		);
		$referenceSnak = new PropertyValueSnak(
			new PropertyId( 'P342' ),
			new StringValue( 'A reference' )
		);

		$statement1->setQualifiers(
			new SnakList( [ $qualifierSnak1, $qualifierSnak2 ] )
		);

		$statement1->addNewReference( $referenceSnak );

		$stringProperty->getStatements()->addStatement( $statement1 );
		$this->siteLinkLookup->putEntity( $stringProperty );
		$this->siteLinkLookup->putEntity( $itemProperty );

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

		$siteLinks = [ $siteLink ];
		$siteLinks[] = new SiteLink(
			'fooSiteId',
			'FooBarFoo'
		);

		$labels = [
			'de' => 'Lua Test Item',
			'en' => 'Test all the code paths'
		];

		$this->createTestItem( new ItemId( 'Q32487' ), $labels, [ $statement1, $statement2 ], $siteLinks );

		$this->createTestItem( new ItemId( 'Q32488' ), [], [ $statement1, $statement3 ], [] );

		$this->createTestItem( new ItemId( 'Q32489' ), [], [ $statement1, $statement1 ], [] );

		// Create another test item to test arbitrary access
		$this->createTestItem( new ItemId( 'Q199024' ), [ 'de' => 'Arbitrary access \o/' ] );

		$this->createTestItem( new ItemId( 'Q885588' ), [ 'ku-latn' => 'Pisîk' ] );
	}

	/**
	 * @param PropertyId $id
	 * @param string $dataTypeId
	 * @param string $label
	 *
	 * @return Property
	 */
	private function getTestProperty( PropertyId $id, $dataTypeId, $label ) {
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
			$item->setStatements( new StatementList( $statements ) );
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
