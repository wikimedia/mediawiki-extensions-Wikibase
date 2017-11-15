<?php

namespace Wikibase\Repo\Tests\Content;

use DataValues\StringValue;
use MWException;
use Wikibase\Content\EntityInstanceHolder;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\EntityContent;
use Wikibase\ItemContent;
use Wikibase\Repo\Content\ItemHandler;
use Wikibase\Repo\Search\Elastic\Fields\FieldDefinitions;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\SettingsArray;

/**
 * @covers Wikibase\Repo\Content\ItemHandler
 * @covers Wikibase\Repo\Content\EntityHandler
 *
 * @group Wikibase
 * @group WikibaseItem
 * @group WikibaseEntity
 * @group WikibaseEntityHandler
 * @group Database
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class ItemHandlerTest extends EntityHandlerTest {

	/**
	 * @see EntityHandlerTest::getModelId
	 * @return string
	 */
	public function getModelId() {
		return CONTENT_MODEL_WIKIBASE_ITEM;
	}

	public function testGetModelID() {
		$this->assertSame( CONTENT_MODEL_WIKIBASE_ITEM, $this->getHandler()->getModelID() );
	}

	/**
	 * @see EntityHandlerTest::contentProvider
	 */
	public function contentProvider() {
		/** @var ItemContent $content */
		$content = $this->newEntityContent();
		$content->getItem()->setAliases( 'en', [ 'foo' ] );
		$content->getItem()->setDescription( 'de', 'foobar' );
		$content->getItem()->setDescription( 'en', 'baz' );
		$content->getItem()->setLabel( 'nl', 'o_O' );

		/** @var ItemContent $withSiteLink */
		$withSiteLink = $content->copy();
		$withSiteLink->getItem()->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Foobar' );

		return [
			[ $this->newEntityContent() ],
			[ $content ],
			[ $withSiteLink ],
		];
	}

	public function provideGetUndoContent() {
		$cases = parent::provideGetUndoContent();

		$item1 = $this->newEntity();
		$item1->setLabel( 'en', 'Foo' );

		$item2 = $this->newEntity();
		$item2->setLabel( 'en', 'Bar' );

		$itemContent1 = $this->newRedirectItemContent( $item1->getId(), new ItemId( 'Q112' ) );
		$itemContent2 = $this->newRedirectItemContent( $item1->getId(), new ItemId( 'Q113' ) );

		$rev1 = $this->fakeRevision( $this->newEntityContent( $item1 ), 11 );
		$rev2 = $this->fakeRevision( $itemContent1, 12 );
		$rev3 = $this->fakeRevision( $itemContent2, 13 );
		$rev4 = $this->fakeRevision( $this->newEntityContent( $item2 ), 14 );

		$cases[] = [ $rev2, $rev2, $rev1, $this->newEntityContent( $item1 ), "undo redirect" ];
		$cases[] = [ $rev3, $rev3, $rev2, $itemContent1, "undo redirect change" ];
		$cases[] = [ $rev3, $rev2, $rev1, null, "undo redirect conflict" ];
		$cases[] = [ $rev4, $rev4, $rev3, $itemContent2, "redo redirect" ];

		return $cases;
	}

	/**
	 * @param ItemId $id
	 * @param ItemId $targetId
	 *
	 * @return ItemContent
	 */
	protected function newRedirectItemContent( ItemId $id, ItemId $targetId ) {
		$redirect = new EntityRedirect( $id, $targetId );

		$handler = $this->getHandler();
		$title = $handler->getTitleForId( $redirect->getTargetId() );

		// set content model to avoid db call to look up content model when
		// constructing ItemContent in the tests, especially in the data providers.
		$title->setContentModel( $handler->getModelID() );

		return ItemContent::newFromRedirect( $redirect, $title );
	}

	/**
	 * @param EntityDocument|null $entity
	 *
	 * @return EntityContent
	 */
	protected function newEntityContent( EntityDocument $entity = null ) {
		if ( !$entity ) {
			$entity = new Item( new ItemId( 'Q42' ) );
		}

		return $this->getHandler()->makeEntityContent( new EntityInstanceHolder( $entity ) );
	}

	public function testMakeEntityRedirectContent() {
		$q2 = new ItemId( 'Q2' );
		$q3 = new ItemId( 'Q3' );
		$redirect = new EntityRedirect( $q2, $q3 );

		$handler = $this->getHandler();
		$target = $handler->getTitleForId( $q3 );
		$content = $handler->makeEntityRedirectContent( $redirect );

		$this->assertEquals( $redirect, $content->getEntityRedirect() );
		$this->assertEquals( $target->getFullText(), $content->getRedirectTarget()->getFullText() );

		// getEntity() should fail
		$this->setExpectedException( MWException::class );
		$content->getEntity();
	}

	public function entityIdProvider() {
		return [
			[ 'Q7' ],
		];
	}

	protected function newEntity( EntityId $id = null ) {
		if ( !$id ) {
			$id = new ItemId( 'Q7' );
		}

		return new Item( $id );
	}

	/**
	 * @param SettingsArray|null $settings
	 *
	 * @return ItemHandler
	 */
	protected function getHandler( SettingsArray $settings = null ) {
		return $this->getWikibaseRepo( $settings )->newItemHandler();
	}

	public function testAllowAutomaticIds() {
		$handler = $this->getHandler();
		$this->assertTrue( $handler->allowAutomaticIds() );
	}

	public function testCanCreateWithCustomId() {
		$handler = $this->getHandler();
		$id = new ItemId( 'Q7' );
		$this->assertFalse( $handler->canCreateWithCustomId( $id ) );
	}

	protected function getTestContent() {
		$item = new Item();
		$item->getFingerprint()->setLabel( 'en', 'Kitten' );
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Kitten' );
		$item->getStatements()->addNewStatement(
			new PropertyNoValueSnak( new PropertyId( 'P1' ) )
		);

		return ItemContent::newFromItem( $item );
	}

	public function testSupportsRedirects() {
		$this->assertTrue( $this->getHandler()->supportsRedirects() );
	}

	protected function getExpectedSearchIndexFields() {
		return [ 'label_count', 'statement_count', 'sitelink_count' ];
	}

	/**
	 * @return PropertyDataTypeLookup
	 */
	private function getPropertyDataTypeLookup() {
		$dataTypeLookup = new InMemoryDataTypeLookup();

		$dataTypeLookup->setDataTypeForProperty( new PropertyId( 'P11' ), 'external-id' );
		$dataTypeLookup->setDataTypeForProperty( new PropertyId( 'P12' ), 'string' );
		$dataTypeLookup->setDataTypeForProperty( new PropertyId( 'P13' ), 'item' );

		return $dataTypeLookup;
	}

	/**
	 * @return ItemHandler
	 */
	private function getItemHandlerWithMockedPropertyDataTypeLookup() {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		return new ItemHandler(
			$wikibaseRepo->getStore()->getTermIndex(),
			$wikibaseRepo->getEntityContentDataCodec(),
			$wikibaseRepo->getEntityConstraintProvider(),
			$wikibaseRepo->getValidatorErrorLocalizer(),
			$wikibaseRepo->getEntityIdParser(),
			$wikibaseRepo->getStore()->newSiteLinkStore(),
			$wikibaseRepo->getEntityIdLookup(),
			$wikibaseRepo->getLanguageFallbackLabelDescriptionLookupFactory(),
			$this->getMock( FieldDefinitions::class ),
			$this->getPropertyDataTypeLookup()
		);
	}

	/**
	 * @dataProvider provideGetIdentifiersCount
	 */
	public function testGetIdentifiersCount( StatementList $statementList, $expected ) {
		$handler = $this->getItemHandlerWithMockedPropertyDataTypeLookup();

		$this->assertSame( $expected, $handler->getIdentifiersCount( $statementList ) );
	}

	public function provideGetIdentifiersCount() {

		$statementIdentifier = new Statement(
			new PropertyValueSnak( new PropertyId( 'P11' ), new StringValue( 'xyz123' ) )
		);
		$statementString = new Statement(
			new PropertyValueSnak( new PropertyId( 'P12' ), new StringValue( 'Athena' ) )
		);
		$statementItem = new Statement( new PropertyNoValueSnak( new PropertyId( 'P13' ) ) );

		$statementListNoIdentifier = new StatementList( [ $statementString ] );
		$statementListOneIdentifier = new StatementList( [ $statementIdentifier ] );
		$statementListOneIdentifierAndMore = new StatementList(
			[ $statementIdentifier, $statementString, $statementItem ]
		);
		$statementListTwoIdentifiers = new StatementList(
			[ $statementIdentifier, $statementIdentifier ]
		);
		$statementListTwoIdentifiersAndMore = new StatementList(
			[ $statementIdentifier, $statementIdentifier, $statementString, $statementItem ]
		);

		return [
			'empty' => [ new StatementList(), 0 ],
			'no identifier' => [ $statementListNoIdentifier, 0 ],
			'one identifier' => [ $statementListOneIdentifier, 1 ],
			'one identifier and more statements' => [ $statementListOneIdentifierAndMore, 1 ],
			'two identifiers' => [ $statementListTwoIdentifiers, 2 ],
			'two identifiers and more statements' => [ $statementListTwoIdentifiersAndMore, 2 ],
		];
	}

	public function testDataForSearchIndex() {
		$handler = $this->getHandler();
		$engine = $this->getMock( \SearchEngine::class );

		$page = $this->getMockWikiPage( $handler );

		$data = $handler->getDataForSearchIndex( $page, new \ParserOutput(), $engine );
		$this->assertSame( 1, $data['label_count'], 'label_count' );
		$this->assertSame( 1, $data['sitelink_count'], 'sitelink_count' );
		$this->assertSame( 1, $data['statement_count'], 'statement_count' );
	}

}
