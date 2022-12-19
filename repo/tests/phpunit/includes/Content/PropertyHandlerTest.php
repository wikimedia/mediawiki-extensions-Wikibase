<?php

namespace Wikibase\Repo\Tests\Content;

use ParserOutput;
use Title;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\Lib\SettingsArray;
use Wikibase\Repo\Content\EntityContent;
use Wikibase\Repo\Content\EntityInstanceHolder;
use Wikibase\Repo\Content\PropertyContent;
use Wikibase\Repo\Content\PropertyHandler;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Content\PropertyHandler
 * @covers \Wikibase\Repo\Content\EntityHandler
 *
 * @group Wikibase
 * @group WikibaseProperty
 * @group WikibaseEntity
 * @group WikibaseEntityHandler
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyHandlerTest extends EntityHandlerTestCase {

	/**
	 * @see EntityHandlerTestCase::getModelId
	 * @return string
	 */
	public function getModelId() {
		return PropertyContent::CONTENT_MODEL_ID;
	}

	public function testGetModelID() {
		$this->assertSame( PropertyContent::CONTENT_MODEL_ID, $this->getHandler()->getModelID() );
	}

	/**
	 * @inheritDoc
	 */
	public function contentProvider() {
		$contents = [];
		$contents[] = [ $this->newEntityContent() ];

		/** @var PropertyContent $content */
		$content = $this->newEntityContent();
		$content->getEntity()->setAliases( 'en', [ 'foo' ] );
		$content->getEntity()->setDescription( 'de', 'foobar' );
		$content->getEntity()->setDescription( 'en', 'baz' );
		$content->getEntity()->setLabel( 'nl', 'o_O' );
		$contents[] = [ $content ];

		$content = clone $contents[1][0];
		// TODO: add some prop-specific stuff: $content->getProperty()->;
		$contents[] = [ $content ];

		return $contents;
	}

	public function testGetTitleForId() {
		$handler = $this->getHandler();
		$id = new NumericPropertyId( 'P123' );

		$title = $handler->getTitleForId( $id );
		$this->assertEquals( $id->getSerialization(), $title->getText() );
	}

	public function testGetTitlesForIds() {
		$handler = $this->getHandler();
		$id1 = new NumericPropertyId( 'P123' );
		$id2 = new NumericPropertyId( 'P124' );

		$titles = $handler->getTitlesForIds( [ $id1, $id2 ] );
		$this->assertEquals( $id1->getSerialization(), $titles['P123']->getText() );
		$this->assertEquals( $id2->getSerialization(), $titles['P124']->getText() );
	}

	public function testGetTitlesForIds_wrongEntityType() {
		$handler = $this->getHandler();
		$id = new ItemId( 'Q123' );

		$this->expectException( \InvalidArgumentException::class );
		$handler->getTitlesForIds( [ $id ] );
	}

	public function testGetIdForTitle() {
		$handler = $this->getHandler();
		$title = Title::makeTitle( $handler->getEntityNamespace(), 'P123' );

		$id = $handler->getIdForTitle( $title );
		$this->assertEquals( $title->getText(), $id->getSerialization() );
	}

	protected function newEntity( EntityId $id = null ) {
		if ( !$id ) {
			$id = new NumericPropertyId( 'P7' );
		}

		$property = Property::newFromType( 'string' );
		$property->setId( $id );
		return $property;
	}

	public function entityIdProvider() {
		return [
			[ 'P7' ],
		];
	}

	/**
	 * @return PropertyContent
	 */
	protected function newEmptyContent() {
		return new PropertyContent();
	}

	/**
	 * @param SettingsArray|null $settings
	 *
	 * @return PropertyHandler
	 */
	protected function getHandler( SettingsArray $settings = null ) {
		$this->getWikibaseRepo( $settings ); // updates services as needed
		return WikibaseRepo::getPropertyHandler();
	}

	protected function newEntityContent( EntityDocument $entity = null ): EntityContent {
		if ( $entity === null ) {
			$entity = $this->newEntity();
		}

		return new PropertyContent( new EntityInstanceHolder( $entity ) );
	}

	protected function newRedirectContent( EntityId $id, EntityId $target ): ?EntityContent {
		return null;
	}

	public function testAllowAutomaticIds() {
		$handler = $this->getHandler();
		$this->assertTrue( $handler->allowAutomaticIds() );
	}

	public function testCanCreateWithCustomId() {
		$handler = $this->getHandler();
		$id = new NumericPropertyId( 'P7' );
		$this->assertFalse( $handler->canCreateWithCustomId( $id ) );
	}

	protected function getTestContent() {
		$property = new Property( null, null, 'string' );
		$property->getFingerprint()->setLabel( 'en', 'Kitten' );
		$property->getStatements()->addNewStatement(
			new PropertyNoValueSnak( new NumericPropertyId( 'P1' ) )
		);

		return PropertyContent::newFromProperty( $property );
	}

	protected function getExpectedSearchIndexFields() {
		return [];
	}

	public function providePageProperties() {
		yield from parent::providePageProperties();

		$contentWithClaim = $this->newEntityContent();
		$snak = new PropertyNoValueSnak( 83 );
		$guid = '$testing$';
		$contentWithClaim->getEntity()->getStatements()->addNewStatement( $snak, null, null, $guid );

		yield 'claims' => [
			$contentWithClaim,
			[ 'wb-claims' => 1 ],
		];
	}

	public function testDataForSearchIndex() {
		$handler = $this->getHandler();
		$engine = $this->createMock( \SearchEngine::class );

		$page = $this->getMockWikiPage( $handler );
		$revision = $page->getRevisionRecord();

		$data = $handler->getDataForSearchIndex( $page, new \ParserOutput(), $engine, $revision );
		$this->assertSame( PropertyContent::CONTENT_MODEL_ID, $data['content_model'], 'content_modek' );
		$this->assertSame( "Kitten", $data['text'], 'text' );
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
}
