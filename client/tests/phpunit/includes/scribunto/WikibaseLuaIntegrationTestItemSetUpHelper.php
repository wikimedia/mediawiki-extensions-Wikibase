<?php

namespace Wikibase\Client\Scribunto\Test;

use DataValues\DataValue;
use DataValues\StringValue;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\SnakFactory;
use Wikibase\Test\MockClientStore;
use Wikibase\Test\MockRepository;

/**
 * Helper class for Lua integration tests.
 *
 * @license GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class WikibaseLuaIntegrationTestItemSetUpHelper {

	/**
	 * @var MockRepository
	 */
	protected $mockRepository;

	public function __construct() {
		$clientStore = new MockClientStore();
		$this->mockRepository = $clientStore->getEntityLookup();
	}

	/**
	 * Sets up the test data.
	 */
	public function setUp() {
		$siteLink = new SiteLink(
			WikibaseClient::getDefaultInstance()->getSettings()->getSetting( 'siteGlobalID' ),
			'WikibaseClientLuaTest'
		);

		if ( $this->mockRepository->getEntityIdForSiteLink( $siteLink ) ) {
			// Already set up for this MockRepository
			return;
		}

		$stringProperty = $this->createTestProperty( 'string', 'LuaTestStringProperty' );

		$stringSnak = $this->getTestSnak(
			$stringProperty->getId(),
			new StringValue( 'Lua :)' )
		);

		$statement1 = $this->getTestStatement( $stringSnak );
		$statement1->setRank( Claim::RANK_PREFERRED );

		$stringSnak2 = $this->getTestSnak(
			$stringProperty->getId(),
			new StringValue( 'This is clearly superior to the parser function' )
		);

		$statement2 = $this->getTestStatement( $stringSnak2 );
		$statement2->setRank( Claim::RANK_NORMAL );

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

		// Create another test item to test arbitrary access
		$this->createTestItem( new ItemId( 'Q199024' ), array( 'de' => 'Arbitrary access \o/' ) );
	}

	/**
	 * @param string $dataTypeId
	 * @param string $label
	 *
	 * @return Property
	 */
	protected function createTestProperty( $dataTypeId, $label ) {
		$property = Property::newFromType( $dataTypeId );
		$property->setLabel( 'de', $label );

		$this->mockRepository->putEntity( $property );

		return $property;
	}

	/**
	 * @param ItemId $id
	 * @param string[] $labels
	 * @param Claim[]|null $claims
	 * @param SiteLink[]|null $siteLinks
	 *
	 * @return Item
	 */
	protected function createTestItem( ItemId $id, array $labels, array $claims = null, array $siteLinks = null ) {
		$item = Item::newEmpty();
		$item->setId( $id );
		$item->setLabels( $labels );

		if ( is_array( $siteLinks ) ) {
			foreach( $siteLinks as $siteLink ) {
				$item->addSiteLink( $siteLink );
			}
		}

		if ( is_array( $claims ) ) {
			foreach( $claims as $claim ) {
				$item->addClaim( $claim );
			}
		}

		$this->mockRepository->putEntity( $item );

		return $item;
	}

	/**
	 * @param PropertyId $propertyId
	 * @param DataValue $value
	 *
	 * @return Snak
	 */
	protected function getTestSnak( PropertyId $propertyId, DataValue $value ) {
		$snakFactory = new SnakFactory();
		$snak = $snakFactory->newSnak( $propertyId, 'value', $value );

		return $snak;
	}

	/**
	 * @param Snak $mainSnak
	 *
	 * @return Statement
	 */
	protected function getTestStatement( Snak $mainSnak ) {
		$statement = new Statement( $mainSnak );
		$statement->setGuid( uniqid( 'kittens', true ) );

		return $statement;
	}

}
