<?php

namespace Wikibase\Repo\Tests\Content;

use DataValues\StringValue;
use MWException;
use ParserOutput;
use Title;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\NullEntityTermStoreWriter;
use Wikibase\Repo\Content\EntityContent;
use Wikibase\Repo\Content\EntityInstanceHolder;
use Wikibase\Repo\Content\ItemContent;
use Wikibase\Repo\Content\ItemHandler;
use Wikibase\Repo\Search\Fields\FieldDefinitions;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Content\ItemHandler
 * @covers \Wikibase\Repo\Content\EntityHandler
 *
 * @group Wikibase
 * @group WikibaseItem
 * @group WikibaseEntity
 * @group WikibaseEntityHandler
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class ItemHandlerTest extends EntityHandlerTestCase {

	/**
	 * @see EntityHandlerTestCase::getModelId
	 * @return string
	 */
	public function getModelId() {
		return ItemContent::CONTENT_MODEL_ID;
	}

	public function testGetModelID() {
		$this->assertSame( ItemContent::CONTENT_MODEL_ID, $this->getHandler()->getModelID() );
	}

	/**
	 * @inheritDoc
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

		$itemContent1 = $this->newRedirectContent( $item1->getId(), new ItemId( 'Q112' ) );
		$itemContent2 = $this->newRedirectContent( $item1->getId(), new ItemId( 'Q113' ) );

		$content1 = $this->newEntityContent( $item1 );
		$content2 = $itemContent1;
		$content3 = $itemContent2;
		$content4 = $this->newEntityContent( $item2 );

		$cases[] = [ $content2, $content2, $content1, $this->newEntityContent( $item1 ), "undo redirect" ];
		$cases[] = [ $content3, $content3, $content2, $itemContent1, "undo redirect change" ];
		$cases[] = [ $content3, $content2, $content1, null, "undo redirect conflict" ];
		$cases[] = [ $content4, $content4, $content3, $itemContent2, "redo redirect" ];

		return $cases;
	}

	/**
	 * @return ItemContent
	 */
	protected function newEmptyContent() {
		return new ItemContent();
	}

	/**
	 * @param EntityDocument|null $entity
	 *
	 * @return EntityContent
	 */
	protected function newEntityContent( EntityDocument $entity = null ): EntityContent {
		if ( !$entity ) {
			$entity = new Item( new ItemId( 'Q42' ) );
		}

		return new ItemContent( new EntityInstanceHolder( $entity ) );
	}

	protected function newRedirectContent( EntityId $id, EntityId $target ): EntityContent {
		$redirect = new EntityRedirect( $id, $target );

		$title = Title::makeTitle( 100, $target->getSerialization() );
		// set content model to avoid db call to look up content model when
		// constructing ItemContent in the tests, especially in the data providers.
		$title->setContentModel( ItemContent::CONTENT_MODEL_ID );

		return new ItemContent( null, $redirect, $title );
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
		$this->expectException( MWException::class );
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
		$this->getWikibaseRepo( $settings ); // updates services as needed
		return WikibaseRepo::getItemHandler();
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
			new PropertyNoValueSnak( new NumericPropertyId( 'P1' ) )
		);

		return ItemContent::newFromItem( $item );
	}

	public function testSupportsRedirects() {
		$this->assertTrue( $this->getHandler()->supportsRedirects() );
	}

	protected function getExpectedSearchIndexFields() {
		return [];
	}

	public function providePageProperties() {
		yield from parent::providePageProperties();

		$contentLinkStub = $this->newEntityContent();
		$contentLinkStub->getItem()->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Foo' );

		yield 'sitelinks' => [
			$contentLinkStub,
			[ 'wb-claims' => 0, 'wb-sitelinks' => 1 ],
		];

		$contentWithClaim = $this->newEntityContent();
		$snak = new PropertyNoValueSnak( 83 );
		$guid = '$testing$';
		$contentWithClaim->getItem()->getStatements()->addNewStatement( $snak, null, null, $guid );

		yield 'claims' => [
			$contentWithClaim,
			[ 'wb-claims' => 1 ],
		];
	}

	/**
	 * @return PropertyDataTypeLookup
	 */
	private function getPropertyDataTypeLookup() {
		$dataTypeLookup = new InMemoryDataTypeLookup();

		$dataTypeLookup->setDataTypeForProperty( new NumericPropertyId( 'P11' ), 'external-id' );
		$dataTypeLookup->setDataTypeForProperty( new NumericPropertyId( 'P12' ), 'string' );
		$dataTypeLookup->setDataTypeForProperty( new NumericPropertyId( 'P13' ), 'item' );

		return $dataTypeLookup;
	}

	/**
	 * @return ItemHandler
	 */
	private function getItemHandlerWithMockedPropertyDataTypeLookup() {
		return new ItemHandler(
			new NullEntityTermStoreWriter(),
			WikibaseRepo::getEntityContentDataCodec(),
			WikibaseRepo::getEntityConstraintProvider(),
			WikibaseRepo::getValidatorErrorLocalizer(),
			WikibaseRepo::getEntityIdParser(),
			WikibaseRepo::getStore()->newSiteLinkStore(),
			WikibaseRepo::getBagOStuffSiteLinkConflictLookup(),
			WikibaseRepo::getEntityIdLookup(),
			WikibaseRepo::getFallbackLabelDescriptionLookupFactory(),
			$this->createMock( FieldDefinitions::class ),
			$this->getPropertyDataTypeLookup(),
			WikibaseRepo::getRepoDomainDbFactory()->newRepoDb()
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
			new PropertyValueSnak( new NumericPropertyId( 'P11' ), new StringValue( 'xyz123' ) )
		);
		$statementString = new Statement(
			new PropertyValueSnak( new NumericPropertyId( 'P12' ), new StringValue( 'Athena' ) )
		);
		$statementItem = new Statement( new PropertyNoValueSnak( new NumericPropertyId( 'P13' ) ) );

		$statementListNoIdentifier = new StatementList( $statementString );
		$statementListOneIdentifier = new StatementList( $statementIdentifier );
		$statementListOneIdentifierAndMore = new StatementList(
			$statementIdentifier, $statementString, $statementItem
		);
		$statementListTwoIdentifiers = new StatementList(
			$statementIdentifier, $statementIdentifier
		);
		$statementListTwoIdentifiersAndMore = new StatementList(
			$statementIdentifier, $statementIdentifier, $statementString, $statementItem
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
		$engine = $this->createMock( \SearchEngine::class );

		$page = $this->getMockWikiPage( $handler );
		$revision = $page->getRevisionRecord();

		$data = $handler->getDataForSearchIndex( $page, new \ParserOutput(), $engine, $revision );
		$this->assertSame( ItemContent::CONTENT_MODEL_ID, $data['content_model'], 'content_modek' );
		$this->assertSame( "Kitten\nKitten", $data['text'], 'text' );
	}

	public function testGetParserOutput() {
		$content = $this->newEntityContent();
		$contentRenderer = $this->getServiceContainer()->getContentRenderer();

		$title = Title::makeTitle( NS_MAIN, 'Foo' );
		$parserOutput = $contentRenderer->getParserOutput( $content, $title );

		$expectedUsedOptions = [ 'userlang', 'wb', 'termboxVersion' ];
		$actualOptions = $parserOutput->getUsedOptions();
		$this->assertEqualsCanonicalizing(
			$expectedUsedOptions,
			$actualOptions,
			'Cache-split flags are not what they should be'
		);

		$this->assertInstanceOf( ParserOutput::class, $parserOutput );
	}

	public function testGetParserOutput_redirect() {
		$content = $this->newRedirectContent( new ItemId( 'Q5' ), new ItemId( 'Q123' ) );
		$contentRenderer = $this->getServiceContainer()->getContentRenderer();
		$title = Title::makeTitle( NS_MAIN, 'Foo' );
		$parserOutput = $contentRenderer->getParserOutput( $content, $title );

		$html = $parserOutput->getText();

		$this->assertStringContainsString( '<div class="redirectMsg">', $html, 'redirect message' );
		$this->assertStringContainsString( '<a href="', $html, 'redirect target link' );
		$this->assertStringContainsString( 'Q123</a>', $html, 'redirect target label' );
	}
}
