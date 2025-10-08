<?php

namespace Wikibase\Repo\Tests\ParserOutput;

use DataValues\QuantityValue;
use MediaWiki\Parser\ParserOutputLinkTypes;
use MediaWikiIntegrationTestCase;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\ParserOutput\FullEntityParserOutputGenerator
 * @covers \Wikibase\Repo\ParserOutput\StatslibTimeRecordingEntityParserOutputGenerator
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class FullEntityParserOutputGeneratorIntegrationTest extends MediaWikiIntegrationTestCase {

	/**
	 * @var EntityStore
	 */
	private $entityStore;

	/**
	 * @var WikibaseRepo
	 */
	private $repo;

	private string $itemNamespace;

	private string $propertyNamespace;

	protected function setUp(): void {
		parent::setUp();

		$this->entityStore = WikibaseRepo::getEntityStore();

		$namespaceLookup = WikibaseRepo::getEntityNamespaceLookup();
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
			new NumericPropertyId( $propertyId ),
			QuantityValue::newFromNumber(
				1,
				WikibaseRepo::getItemVocabularyBaseUri() . $unitItemId
			)
		) );

		$parserOutput = $this->newParserOutputGenerator()->getParserOutput( new EntityRevision( $item, $revision ) );

		$links = $parserOutput->getLinkList( ParserOutputLinkTypes::LOCAL );
		$this->assertTrue( array_any( $links, fn( $item ) =>
			$item['link']->getNamespace() === (int)$this->propertyNamespace &&
			$item['link']->getDBkey() === $propertyId
		), var_export( $links, true ) );
		$this->assertTrue( array_any( $links, fn( $item ) =>
			$item['link']->getNamespace() === (int)$this->itemNamespace &&
			$item['link']->getDBkey() === $unitItemId
		) );
	}

	public function testSetsViewChunksForEntityTermsView() {
		$parserOutputGenerator = $this->newParserOutputGenerator();

		$parserOutput = $parserOutputGenerator->getParserOutput(
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
			array_values( $parserOutput->getExtensionData( 'wikibase-view-chunks' ) )
		);
		$this->assertArrayHasKey( 'en', $parserOutput->getExtensionData( 'wikibase-terms-list-items' ) );
	}

	private function newParserOutputGenerator() {
		return WikibaseRepo::getEntityParserOutputGeneratorFactory()
			->getEntityParserOutputGenerator( $this->getServiceContainer()->getLanguageFactory()->getLanguage( 'en' ) );
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
			new Property( new NumericPropertyId( $id ), null, 'wikibase-item' ),
			__METHOD__,
			$this->getTestUser()->getUser()
		);
	}

	public function testGetParserOutputIncludesLabelsOfRedirectEntityUsedAsStatementValue() {
		$mwServices = $this->getServiceContainer();

		$property = new Property( new NumericPropertyId( 'P93' ), null, 'wikibase-item' );
		$item = new Item( new ItemId( 'Q303' ) );

		$redirectSourceId = new ItemId( 'Q809' );
		$redirectSource = new Item( $redirectSourceId );
		$redirectSource->setLabel( 'en', 'redirect label' );

		$redirectTargetId = new ItemId( 'Q808' );
		$redirectTarget = new Item( $redirectTargetId );
		$redirectTarget->setLabel( 'en', 'target label' );

		$item->getStatements()->addNewStatement( new PropertyValueSnak( $property->getId(), new EntityIdValue( $redirectSourceId ) ) );

		$user = $this->getTestUser()->getUser();
		$store = WikibaseRepo::getEntityStore( $mwServices );
		$store->saveEntity( $property, 'test property', $user );
		$store->saveEntity( $redirectSource, 'test item', $user );
		$store->saveEntity( $redirectTarget, 'test item', $user );
		$store->saveRedirect( new EntityRedirect( $redirectSourceId, $redirectTargetId ), 'mistake', $user );
		$revision = $store->saveEntity( $item, 'test item', $user );

		$language = $mwServices->getLanguageFactory()->getLanguage( 'en' );
		$entityParserOutputGeneratorFactory = WikibaseRepo::getEntityParserOutputGeneratorFactory( $mwServices );
		$entityParserOutputGenerator = $entityParserOutputGeneratorFactory->getEntityParserOutputGenerator( $language );

		$parserOutput = $entityParserOutputGenerator->getParserOutput( $revision );

		$this->assertStringContainsString( 'target label', $parserOutput->getRawText() );
	}

}
