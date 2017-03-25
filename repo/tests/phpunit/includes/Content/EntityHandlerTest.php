<?php

namespace Wikibase\Repo\Tests\Content;

use ContentHandler;
use DataValues\Serializers\DataValueSerializer;
use DummySearchIndexFieldDefinition;
use FauxRequest;
use InvalidArgumentException;
use Language;
use MWException;
use RequestContext;
use Revision;
use RuntimeException;
use Title;
use Wikibase\Content\EntityInstanceHolder;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\Term\LabelsProvider;
use Wikibase\EntityContent;
use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\RepositoryDefinitions;
use Wikibase\Repo\Content\EntityHandler;
use Wikibase\Repo\Content\ItemHandler;
use Wikibase\Repo\Validators\EntityValidator;
use Wikibase\Repo\Validators\ValidatorErrorLocalizer;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\SettingsArray;
use WikiPage;
use WikitextContent;

/**
 * @covers Wikibase\Repo\Content\EntityHandler
 *
 * @group Wikibase
 * @group WikibaseEntity
 * @group WikibaseEntityHandler
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
abstract class EntityHandlerTest extends \MediaWikiTestCase {

	abstract public function getModelId();

	/**
	 * @param SettingsArray|null $settings
	 *
	 * @return WikibaseRepo
	 */
	protected function getWikibaseRepo( SettingsArray $settings = null ) {
		$repoSettings = WikibaseRepo::getDefaultInstance()->getSettings()->getArrayCopy();

		if ( $settings ) {
			$repoSettings = array_merge( $repoSettings, $settings->getArrayCopy() );
		}

		/** @var RepositoryDefinitions $repositoryDefinitions */
		$repositoryDefinitions = $this->getMockBuilder( RepositoryDefinitions::class )
			->disableOriginalConstructor()
			->getMock();

		return new WikibaseRepo(
			new SettingsArray( $repoSettings ),
			new DataTypeDefinitions( array() ),
			new EntityTypeDefinitions( require __DIR__ . '/../../../../../lib/WikibaseLib.entitytypes.php' ),
			$repositoryDefinitions
		);
	}

	/**
	 * @param SettingsArray|null $settings
	 *
	 * @return EntityHandler
	 */
	abstract protected function getHandler( SettingsArray $settings = null );

	/**
	 * @param EntityDocument|null $entity
	 *
	 * @return EntityContent
	 */
	protected function newEntityContent( EntityDocument $entity = null ) {
		if ( !$entity ) {
			$entity = $this->newEntity();
		}

		$handler = $this->getHandler();
		return $handler->makeEntityContent( new EntityInstanceHolder( $entity ) );
	}

	/**
	 * @param EntityId $id
	 * @param EntityId $target
	 *
	 * @return EntityContent
	 */
	protected function newRedirectContent( EntityId $id, EntityId $target ) {
		$handler = $this->getHandler();
		return $handler->makeEntityRedirectContent( new EntityRedirect( $id, $target ) );
	}

	/**
	 * @param EntityId|null $id
	 *
	 * @return EntityDocument
	 */
	abstract protected function newEntity( EntityId $id = null );

	/**
	 * Returns EntityContents that can be handled by the EntityHandler deriving class.
	 *
	 * @return array[]
	 */
	abstract public function contentProvider();

	public function testGetSpecialPageForCreation() {
		$specialPageName = $this->getHandler()->getSpecialPageForCreation();
		$this->assertTrue( $specialPageName === null || is_string( $specialPageName ) );
	}

	public function testGivenNonEntityContent_serializeContentThrowsException() {
		$handler = $this->getHandler();
		$content = new WikitextContent( '' );
		$this->setExpectedException( InvalidArgumentException::class );
		$handler->serializeContent( $content );
	}

	/**
	 * @dataProvider contentProvider
	 * @param EntityContent $content
	 */
	public function testSerialization( EntityContent $content ) {
		$handler = $this->getHandler();

		foreach ( array( CONTENT_FORMAT_JSON,  CONTENT_FORMAT_SERIALIZED ) as $format ) {
			$this->assertTrue( $content->equals(
				$handler->unserializeContent( $handler->serializeContent( $content, $format ), $format )
			) );
		}
	}

	public function testCanBeUsedOn() {
		$handler = $this->getHandler();

		$this->assertTrue( $handler->canBeUsedOn( Title::makeTitle( $handler->getEntityNamespace(), "1234" ) ),
							'It should be possible to create this kind of entity in the respective entity namespace!'
						);

		$this->assertFalse( $handler->canBeUsedOn( Title::makeTitle( NS_MEDIAWIKI, "Foo" ) ),
							'It should be impossible to create an entity outside the respective entity namespace!'
						);
	}

	public function testIsParserCacheSupported() {
		$this->assertTrue( $this->getHandler()->isParserCacheSupported() );
	}

	public function testGetPageLanguage() {
		global $wgContLang;

		$handler = $this->getHandler();
		$title = Title::makeTitle( $handler->getEntityNamespace(), "1234567" );

		//NOTE: currently, this tests whether getPageLanguage will always return the content language, even
		//      if the user language is different. It's unclear whether this is actually the desired behavior,
		//      since Wikibase Entities are inherently multilingual, so they have no actual "page language".

		// test whatever is there
		$this->assertEquals( $wgContLang->getCode(), $handler->getPageLanguage( $title )->getCode() );

		// test fr
		$this->setMwGlobals( 'wgLang', Language::factory( "fr" ) );
		$handler = $this->getHandler();
		$this->assertEquals( $wgContLang->getCode(), $handler->getPageLanguage( $title )->getCode() );

		// test nl
		$this->setMwGlobals( 'wgLang', Language::factory( "nl" ) );
		$this->setMwGlobals( 'wgContLang', Language::factory( "fr" ) );
		$handler = $this->getHandler();
		$this->assertEquals( $wgContLang->getCode(), $handler->getPageLanguage( $title )->getCode() );
	}

	public function testGetPageViewLanguage() {
		global $wgLang;

		$handler = $this->getHandler();
		$title = Title::makeTitle( $handler->getEntityNamespace(), "1234567" );

		//NOTE: we expect getPageViewLanguage to return the user language, because Wikibase Entities
		//      are always shown in the user language.

		// test whatever is there
		$this->assertEquals( $wgLang->getCode(), $handler->getPageViewLanguage( $title )->getCode() );

		// test fr
		$this->setMwGlobals( 'wgLang', Language::factory( "fr" ) );
		$handler = $this->getHandler();
		$this->assertEquals( $wgLang->getCode(), $handler->getPageViewLanguage( $title )->getCode() );

		// test nl
		$this->setMwGlobals( 'wgLang', Language::factory( "nl" ) );
		$handler = $this->getHandler();
		$this->assertEquals( $wgLang->getCode(), $handler->getPageViewLanguage( $title )->getCode() );
	}

	public function testLocalizedModelName() {
		$name = ContentHandler::getLocalizedName( $this->getModelId() );

		$this->assertNotEquals( $this->getModelId(), $name, "localization of model name" );
	}

	protected function fakeRevision( EntityContent $content, $id = 0 ) {
		$revision = new Revision( array(
			'id' => $id,
			'page' => $id,
			'content' => $content,
		) );

		return $revision;
	}

	public function provideGetUndoContent() {
		/** @var LabelsProvider $e2 */
		/** @var LabelsProvider $e3 */
		/** @var LabelsProvider $e4 */
		/** @var LabelsProvider $e5 */
		/** @var LabelsProvider $e5u4 */
		/** @var LabelsProvider $e5u4u3 */

		$e1 = $this->newEntity();
		$r1 = $this->fakeRevision( $this->newEntityContent( $e1 ), 1 );

		$e2 = $this->newEntity();
		$e2->getLabels()->setTextForLanguage( 'en', 'Foo' );
		$r2 = $this->fakeRevision( $this->newEntityContent( $e2 ), 2 );

		$e3 = $this->newEntity();
		$e3->getLabels()->setTextForLanguage( 'en', 'Foo' );
		$e3->getLabels()->setTextForLanguage( 'de', 'Fuh' );
		$r3 = $this->fakeRevision( $this->newEntityContent( $e3 ), 3 );

		$e4 = $this->newEntity();
		$e4->getLabels()->setTextForLanguage( 'en', 'Foo' );
		$e4->getLabels()->setTextForLanguage( 'de', 'Fuh' );
		$e4->getLabels()->setTextForLanguage( 'fr', 'Fu' );
		$r4 = $this->fakeRevision( $this->newEntityContent( $e4 ), 4 );

		$e5 = $this->newEntity();
		$e5->getLabels()->setTextForLanguage( 'en', 'F00' );
		$e5->getLabels()->setTextForLanguage( 'de', 'Fuh' );
		$e5->getLabels()->setTextForLanguage( 'fr', 'Fu' );
		$r5 = $this->fakeRevision( $this->newEntityContent( $e5 ), 5 );

		$e5u4 = $this->newEntity();
		$e5u4->getLabels()->setTextForLanguage( 'en', 'F00' );
		$e5u4->getLabels()->setTextForLanguage( 'de', 'Fuh' );

		$e5u4u3 = $this->newEntity();
		$e5u4u3->getLabels()->setTextForLanguage( 'en', 'F00' );

		return array(
			array( $r5, $r5, $r4, $this->newEntityContent( $e4 ), "undo last edit" ),
			array( $r5, $r4, $r3, $this->newEntityContent( $e5u4 ), "undo previous edit" ),

			array( $r5, $r5, $r3, $this->newEntityContent( $e3 ), "undo last two edits" ),
			array( $r5, $r4, $r2, $this->newEntityContent( $e5u4u3 ), "undo past two edits" ),

			array( $r5, $r2, $r1, null, "undo conflicting edit" ),
			array( $r5, $r3, $r1, null, "undo two edits with conflict" ),
		);
	}

	/**
	 * @dataProvider provideGetUndoContent
	 *
	 * @param Revision $latestRevision
	 * @param Revision $newerRevision
	 * @param Revision $olderRevision
	 * @param EntityContent|null $expected
	 * @param string $message
	 */
	public function testGetUndoContent(
		Revision $latestRevision,
		Revision $newerRevision,
		Revision $olderRevision,
		EntityContent $expected = null,
		$message
	) {

		$handler = $this->getHandler();
		$undo = $handler->getUndoContent( $latestRevision, $newerRevision, $olderRevision );

		if ( $expected ) {
			$this->assertInstanceOf( EntityContent::class, $undo, $message );
			$this->assertTrue( $expected->equals( $undo ), $message );
		} else {
			$this->assertFalse( $undo, $message );
		}
	}

	public function testGetEntityType() {
		$handler = $this->getHandler();
		$content = $this->newEntityContent();
		$entity = $content->getEntity();

		$this->assertEquals( $entity->getType(), $handler->getEntityType() );
	}

	public function testMakeEntityContent() {
		$entity = $this->newEntity();

		$handler = $this->getHandler();
		$content = $handler->makeEntityContent( new EntityInstanceHolder( $entity ) );

		$this->assertEquals( $this->getModelId(), $content->getModel() );
		$this->assertSame( $entity, $content->getEntity() );
	}

	public function testMakeEmptyContent() {
		$handler = $this->getHandler();
		$entity = $handler->makeEmptyContent()->getEntity();

		$this->assertTrue( $entity->isEmpty(), 'isEmpty' );
		$this->assertEquals( $handler->getEntityType(), $entity->getType(), 'entity type' );
	}

	public function testMakeRedirectContent() {
		// We don't support title based redirects.
		$this->setExpectedException( MWException::class );

		$handler = $this->getHandler();
		$handler->makeRedirectContent( Title::newFromText( 'X11', $handler->getEntityNamespace() ) );
	}

	public function testMakeEmptyEntity() {
		$handler = $this->getHandler();
		$entity = $handler->makeEmptyEntity();

		$this->assertTrue( $entity->isEmpty(), 'isEmpty' );
		$this->assertEquals( $handler->getEntityType(), $entity->getType(), 'entity type' );
	}

	abstract public function entityIdProvider();

	/**
	 * @dataProvider entityIdProvider
	 */
	public function testMakeEntityId( $idString ) {
		$handler = $this->getHandler();
		$id = $handler->makeEntityId( $idString );

		$this->assertEquals( $handler->getEntityType(), $id->getEntityType() );
		$this->assertEquals( $idString, $id->getSerialization() );
	}

	public function exportTransformProvider() {
		$entity = $this->newEntity();

		$internalSerializer = WikibaseRepo::getDefaultInstance()->getEntitySerializer();
		$oldBlob = json_encode( $internalSerializer->serialize( $entity ) );

		// fake several old formats
		$type = $entity->getType();
		$id = $entity->getId()->getSerialization();
		// replace "type":"item","id":"q7" with "entity":["item",7]
		$veryOldBlob = preg_replace(
			'/"type":"\w+"(,"datatype":"\w+")?,"id":"\w\d+"/',
			'"entity":["' . strtolower( $type ) . '",' . substr( $id, 1 ) . ']$1',
			$oldBlob
		);
		// replace "entity":["item",7] with "entity":"q7"
		$veryVeryOldBlob = preg_replace(
			'/"entity":\["\w+",\d+\]/',
			'"entity":"' . strtolower( $id ) . '"',
			$veryOldBlob
		);

		// sanity (cannot compare $veryOldBlob and $oldBlob until we have the new serialization in place)
		if ( $veryVeryOldBlob === $veryOldBlob /* || $veryOldBlob === $oldBlob */ ) {
			throw new RuntimeException( 'Failed to fake very old serialization format based on oldish serialization format.' );
		}

		// make new style blob
		$newSerializerFactory = new SerializerFactory( new DataValueSerializer() );
		$newSerializer = $newSerializerFactory->newEntitySerializer();
		$newBlob = json_encode( $newSerializer->serialize( $entity ) );

		return array(
			'old serialization / ancient id format' => array( $veryVeryOldBlob, $newBlob ),
			'old serialization / new silly id format' => array( $veryOldBlob, $newBlob ),
			'old serialization / old serializer format' => array( $oldBlob, $newBlob ),
			'new serialization format, keep as is' => array( $newBlob, $newBlob ),
		);
	}

	/**
	 * @dataProvider exportTransformProvider
	 */
	public function testExportTransform( $blob, $expected ) {
		$settings = new SettingsArray();
		$settings->setSetting( 'transformLegacyFormatOnExport', true );

		$handler = $this->getHandler( $settings );
		$actual = $handler->exportTransform( $blob );

		$this->assertEquals( $expected, $actual );
	}

	public function testExportTransform_neverRecodeNonLegacyFormat() {
		$settings = new SettingsArray();
		$settings->setSetting( 'transformLegacyFormatOnExport', true );

		$entity = $this->newEntity();
		$entitySerializer = $this->getWikibaseRepo( $settings )->getEntitySerializer();
		$expected = json_encode( $entitySerializer->serialize( $entity ) );

		$handler = $this->getHandler( $settings );
		$actual = $handler->exportTransform( $expected );

		$this->assertEquals( $expected, $actual );
	}

	public function testGetLegacyExportFormatDetector() {
		$detector = $this->getHandler()->getLegacyExportFormatDetector();
		$this->assertInternalType( 'callable', $detector );
	}

	public function forCreationParamProvider() {
		return array(
			array( true ),
			array( false ),
		);
	}

	/**
	 * @dataProvider forCreationParamProvider
	 */
	public function testGetOnSaveValidators( $forCreation ) {
		$handler = $this->getHandler();

		$validators = $handler->getOnSaveValidators( $forCreation );

		$this->assertInternalType( 'array', $validators );

		foreach ( $validators as $validator ) {
			$this->assertInstanceOf( EntityValidator::class, $validator );
		}
	}

	public function testGetValidationErrorLocalizer() {
		$localizer = $this->getHandler()->getValidationErrorLocalizer();
		$this->assertInstanceOf( ValidatorErrorLocalizer::class, $localizer );
	}

	public function testMakeParserOptions() {
		$handler = $this->getHandler();

		$options = $handler->makeParserOptions( 'canonical' );
		$hash = $options->optionsHash( array( 'userlang' ) );

		$this->assertRegExp( '/wb\d+/', $hash, 'contains Wikibase version' );
	}

	public function testShowMissingEntity() {
		$handler = $this->getHandler();

		$title = Title::makeTitle( $handler->getEntityNamespace(), 'MISSING' );

		$context = new RequestContext( new FauxRequest() );
		$context->setLanguage( 'qqx' );
		$context->setTitle( $title );

		$handler->showMissingEntity( $title, $context );

		$this->assertContains( '(wikibase-noentity)', $context->getOutput()->getHTML() );
	}

	public function testSupportsCategories() {
		$this->assertFalse( $this->getHandler()->supportsCategories() );
	}

	public function testFieldsForSearchIndex() {
		$handler = $this->getHandler();

		$searchEngine = $this->getMockBuilder( 'SearchEngine' )->getMock();

		$searchEngine->expects( $this->any() )
			->method( 'makeSearchFieldMapping' )
			->will( $this->returnCallback( function ( $name, $type ) {
				return new DummySearchIndexFieldDefinition( $name, $type );
			} ) );

		$fields = $handler->getFieldsForSearchIndex( $searchEngine );
		$expectedFields = [ 'label_count', 'sitelink_count', 'statement_count' ];
		foreach ( $expectedFields as $expected ) {
			$this->assertInstanceOf( \SearchIndexField::class, $fields[$expected] );
			$mapping = $fields[$expected]->getMapping( $searchEngine );
			$this->assertEquals( $expected, $mapping['name'] );
		}
	}

	abstract protected function getTestItemContent();

	public function testDataForSearchIndex() {
		$handler = $this->getHandler();
		$engine = $this->getMock( \SearchEngine::class );

		$page =
			$this->getMockBuilder( WikiPage::class )
				->setConstructorArgs( [ Title::newFromText( 'Q1' ) ] )
				->getMock();
		$page->method( 'getContent' )->willReturn( $this->getTestItemContent() );

		$data = $handler->getDataForSearchIndex( $page, new \ParserOutput(), $engine );
		$this->assertSame( 1, $data['label_count'], 'label_count' );
		if ( $handler instanceof ItemHandler ) {
			$this->assertSame( 1, $data['sitelink_count'], 'sitelink_count' );
		}
		$this->assertSame( 1, $data['statement_count'], 'statement_count' );
	}

}
