<?php

namespace Wikibase\Repo\Tests\ParserOutput;

use DataValues\QuantityValue;
use Language;
use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Repo\Tests\NewItem;
use Wikibase\Repo\Tests\WikibaseTablesUsed;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\ParserOutput\FullEntityParserOutputGenerator
 * @covers \Wikibase\Repo\ParserOutput\StatsdTimeRecordingEntityParserOutputGenerator
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class FullEntityParserOutputGeneratorIntegrationTest extends MediaWikiIntegrationTestCase {

	use WikibaseTablesUsed;

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

	protected function setUp(): void {
		parent::setUp();

		$this->repo = WikibaseRepo::getDefaultInstance();
		$this->entityStore = $this->repo->getEntityStore();

		$namespaceLookup = $this->repo->getEntityNamespaceLookup();
		$this->propertyNamespace = $namespaceLookup->getEntityNamespace( 'property' );
		$this->itemNamespace = $namespaceLookup->getEntityNamespace( 'item' );
	}

	public function testParserOutputContainsLinksForItemsUsedAsQuantity() {
		$propertyId = 'P123';
		$revision = 4711;
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

		$output = $this->newParserOutputGenerator()->getParserOutput( new EntityRevision( $item, $revision ) );

		$this->assertArrayHasKey(
			$propertyId,
			$output->getLinks()[$this->propertyNamespace]
		);
		$this->assertArrayHasKey(
			$unitItemId,
			$output->getLinks()[$this->itemNamespace]
		);
	}

	public function testSetsViewChunksForEntityTermsView() {
		$parserOutputGenerator = $this->newParserOutputGenerator();

		$output = $parserOutputGenerator->getParserOutput(
			new EntityRevision(
				NewItem::withId( 'Q42' )->build(),
				4711
			),
			true
		);

		$this->assertSame(
			[
				[ 'entityViewPlaceholder-entitytermsview-entitytermsforlanguagelistview-class' ],
				[ 'termbox' ],
			],
			array_values( $output->getExtensionData( 'wikibase-view-chunks' ) )
		);
		$this->assertArrayHasKey( 'en', $output->getExtensionData( 'wikibase-terms-list-items' ) );
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

	public function testGetParserOutputIncludesLabelsOfRedirectEntityUsedAsStatementValue() {
		$this->markTablesUsedForEntityEditing();

		$repo = WikibaseRepo::getDefaultInstance();
		$mwServices = MediaWikiServices::getInstance();

		$property = new Property( new PropertyId( 'P93' ), null, 'wikibase-item' );
		$item = new Item( new ItemId( 'Q303' ) );

		$redirectSourceId = new ItemId( 'Q809' );
		$redirectSource = new Item( $redirectSourceId );
		$redirectSource->setLabel( 'en', 'redirect label' );

		$redirectTargetId = new ItemId( 'Q808' );
		$redirectTarget = new Item( $redirectTargetId );
		$redirectTarget->setLabel( 'en', 'target label' );

		$item->getStatements()->addNewStatement( new PropertyValueSnak( $property->getId(), new EntityIdValue( $redirectSourceId ) ) );

		$user = $this->getTestUser()->getUser();
		$store = $repo->getEntityStore();
		$store->saveEntity( $property, 'test property', $user );
		$store->saveEntity( $redirectSource, 'test item', $user );
		$store->saveEntity( $redirectTarget, 'test item', $user );
		$store->saveRedirect( new EntityRedirect( $redirectSourceId, $redirectTargetId ), 'mistake', $user );
		$revision = $store->saveEntity( $item, 'test item', $user );

		$language = $mwServices->getLanguageFactory()->getLanguage( 'en' );
		$entityParserOutputGenerator = $repo->getEntityParserOutputGenerator( $language );

		$parserOutput = $entityParserOutputGenerator->getParserOutput( $revision );

		$this->assertStringContainsString( 'target label', $parserOutput->getText() );
	}

}
