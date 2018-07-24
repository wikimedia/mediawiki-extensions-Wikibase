<?php

namespace Wikibase\Repo\Tests\ParserOutput;

use DataValues\QuantityValue;
use Language;
use MediaWikiTestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Repo\ParserOutput\EntityParserOutputGenerator
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class EntityParserOutputGeneratorIntegrationTest extends MediaWikiTestCase {

	/**
	 * @var EntityStore
	 */
	private $entityStore;

	/**
	 * @var WikibaseRepo
	 */
	private $repo;

	private $itemNamespace;

	private $propertyNamespace;

	public function setUp() {
		parent::setUp();

		$this->repo = WikibaseRepo::getDefaultInstance();
		$this->entityStore = $this->repo->getEntityStore();

		$namespaceLookup = $this->repo->getEntityNamespaceLookup();
		$this->propertyNamespace = $namespaceLookup->getEntityNamespace( 'property' );
		$this->itemNamespace = $namespaceLookup->getEntityNamespace( 'item' );
	}

	public function testParserOutputContainsLinksForItemsUsedAsQuantity() {
		$propertyId = 'P123';
		$unitItemId = 'Q42';
		$this->saveItem( $unitItemId );
		$this->saveProperty( $propertyId );

		$item = new Item();
		$item->getStatements()->addNewStatement( new PropertyValueSnak(
			new PropertyId( $propertyId ),
			QuantityValue::newFromNumber(
				1,
				$this->repo->getSettings()->getSetting( 'conceptBaseUri' ) . $unitItemId
			)
		) );

		$output = $this->newParserOutputGenerator()->getParserOutput( $item );

		$this->assertArrayHasKey(
			$propertyId,
			$output->getLinks()[$this->propertyNamespace]
		);
		$this->assertArrayHasKey(
			$unitItemId,
			$output->getLinks()[$this->itemNamespace]
		);
	}

	private function newParserOutputGenerator() {
		return WikibaseRepo::getDefaultInstance()->getEntityParserOutputGeneratorFactory()
			->getEntityParserOutputGenerator( Language::factory( 'en' ) );
	}

	private function saveItem( $id ) {
		$this->entityStore->saveEntity(
			new Item( new ItemId( $id ) ),
			__METHOD__,
			$this->getTestUser()->getUser()
		);
	}

	private function saveProperty( $id ) {
		$this->entityStore->saveEntity(
			new Property( new PropertyId( $id ), null, 'wikibase-item' ),
			__METHOD__,
			$this->getTestUser()->getUser()
		);
	}

}
