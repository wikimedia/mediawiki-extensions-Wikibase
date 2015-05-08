<?php

namespace Wikibase\Client\Tests\DataAccess;

use DataValues\DataValue;
use DataValues\StringValue;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\SnakFactory;
use Wikibase\Test\MockClientStore;
use Wikibase\Test\MockRepository;

/**
 * Helper class for Lua integration tests.
 *
 * @license GNU GPL v2+
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

		if ( $this->siteLinkLookup->getEntityIdForSiteLink( $siteLink ) ) {
			// Already set up for this MockRepository
			return;
		}

		$stringProperty = $this->getTestProperty( new PropertyId( 'P342' ), 'string', 'LuaTestStringProperty' );
		$itemProperty = $this->getTestProperty( new PropertyId( 'P456' ), 'wikibase-item', 'LuaTestItemProperty' );

		$stringSnak = $this->getTestSnak(
			$stringProperty->getId(),
			new StringValue( 'Lua :)' )
		);

		$statement1 = $this->getTestStatement( $stringSnak );
		$statement1->setRank( Statement::RANK_PREFERRED );

		$qualifierSnak1 = $this->getTestSnak(
			new PropertyId( 'P342' ),
			new StringValue( 'A qualifier Snak')
		);
		$qualifierSnak2 = $this->getTestSnak(
			new PropertyId( 'P342' ),
			new StringValue( 'Moar qualifiers')
		);
		$referenceSnak = $this->getTestSnak(
			new PropertyId( 'P342' ),
			new StringValue( 'A reference')
		);

		$statement1->setQualifiers(
			new SnakList( array( $qualifierSnak1, $qualifierSnak2 ) )
		);

		$statement1->addNewReference( $referenceSnak );

		$stringProperty->getStatements()->addStatement( $statement1 );
		$this->siteLinkLookup->putEntity( $stringProperty );
		$this->siteLinkLookup->putEntity( $itemProperty );

		$stringSnak2 = $this->getTestSnak(
			$stringProperty->getId(),
			new StringValue( 'Lua is clearly superior to the parser function' )
		);

		$statement2 = $this->getTestStatement( $stringSnak2 );
		$statement2->setRank( Statement::RANK_NORMAL );

		$itemSnak = $this->getTestSnak(
			$itemProperty->getId(),
			new EntityIdValue( new ItemId( 'Q885588' ) )
		);

		$statement3 = $this->getTestStatement( $itemSnak );
		$statement3->setRank( Statement::RANK_NORMAL );

		$siteLinks = array( $siteLink );
		$siteLinks[] = new SiteLink(
			'fooSiteId',
			'FooBarFoo'
		);

		$labels = array(
			'de' => 'Lua Test Item',
			'en' => 'Test all the code paths'
		);

		$this->createTestItem( new ItemId( 'Q32487' ), $labels, array( $statement1, $statement2 ), $siteLinks );

		$this->createTestItem( new ItemId( 'Q32488' ), array(), array( $statement1, $statement3 ), array() );

		// Create another test item to test arbitrary access
		$this->createTestItem( new ItemId( 'Q199024' ), array( 'de' => 'Arbitrary access \o/' ) );

		$this->createTestItem( new ItemId( 'Q885588'), array( 'ku-latn' => 'Pisîk' ) );
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
		$item->setLabels( $labels );

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
	 * @param PropertyId $propertyId
	 * @param DataValue $value
	 *
	 * @return Snak
	 */
	private function getTestSnak( PropertyId $propertyId, DataValue $value ) {
		$snakFactory = new SnakFactory();
		$snak = $snakFactory->newSnak( $propertyId, 'value', $value );

		return $snak;
	}

	/**
	 * @param Snak $mainSnak
	 *
	 * @return Statement
	 */
	private function getTestStatement( Snak $mainSnak ) {
		$statement = new Statement( new Claim( $mainSnak ) );
		$statement->setGuid( uniqid( 'kittens', true ) );

		return $statement;
	}

}
