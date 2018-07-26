<?php

namespace Wikibase\Repo\Tests\Content;

use Action;
use Article;
use ContentHandler;
use DataValues\Serializers\DataValueSerializer;
use DummySearchIndexFieldDefinition;
use FauxRequest;
use InvalidArgumentException;
use Language;
use LogicException;
use MediaWiki\Storage\PageIdentityValue;
use MWException;
use PHPUnit_Framework_MockObject_MockObject;
use RequestContext;
use Revision;
use RuntimeException;
use SearchEngine;
use Serializers\Serializer;
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
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
abstract class EntityHandlerTestCase extends \MediaWikiTestCase {

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

		return new WikibaseRepo(
			new SettingsArray( $repoSettings ),
			new DataTypeDefinitions( [] ),
			$this->getEntityTypeDefinitions(),
			$this->getRepositoryDefinitions()
		);
	}

	protected function getEntityTypeDefinitions() {
		return new EntityTypeDefinitions(
			array_merge_recursive(
				require __DIR__ . '/../../../../../lib/WikibaseLib.entitytypes.php',
				require __DIR__ . '/../../../../WikibaseRepo.entitytypes.php'
			)
		);
	}

	/**
	 * @return RepositoryDefinitions
	 */
	private function getRepositoryDefinitions() {
		return new RepositoryDefinitions(
			[ '' => [ 'database' => '', 'base-uri' => '', 'entity-namespaces' => [], 'prefix-mapping' => [] ] ],
			new EntityTypeDefinitions( [] )
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
	 * Returns EntityContents that can be serialized by the EntityHandler deriving class.
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

		foreach ( [ CONTENT_FORMAT_JSON,  CONTENT_FORMAT_SERIALIZED ] as $format ) {
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
		$this->setContentLang( 'fr' );
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
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->method( 'exists' )
			->will( $this->returnValue( true ) );

		$title->method( 'getArticleID' )
			->will( $this->returnValue( $id ) );

		$title->method( 'getLatestRevID' )
			->will( $this->returnValue( $id ) );

		// TODO: remove conditional as soon as Title::getPageIdentity() is in core.
		if ( method_exists( Title::class, 'getPageIdentity' ) ) {
			$page = PageIdentityValue::newFromDBKey( $id, NS_MAIN, __CLASS__ );
			$title->method( 'getPageIdentity' )
				->will( $this->returnValue( $page ) );
		}

		$revision = new Revision( [
			'id' => $id,
			'title' => $title,
			'content' => $content,
		] );

		return $revision;
	}

	public function provideGetUndoContent() {
		/** @var LabelsProvider $e2 */
		/** @var LabelsProvider $e3 */
		/** @var LabelsProvider $e4 */
		/** @var LabelsProvider $e5 */
		/** @var LabelsProvider $e5u4 */
		/** @var LabelsProvider $e5u4u3 */

		if ( !$this->newEntity() instanceof LabelsProvider ) {
			$this->markTestSkipped( 'provideGetUndoContent only works for entities that have labels field' );
		}

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

		return [
			[ $r5, $r5, $r4, $this->newEntityContent( $e4 ), "undo last edit" ],
			[ $r5, $r4, $r3, $this->newEntityContent( $e5u4 ), "undo previous edit" ],

			[ $r5, $r5, $r3, $this->newEntityContent( $e3 ), "undo last two edits" ],
			[ $r5, $r4, $r2, $this->newEntityContent( $e5u4u3 ), "undo past two edits" ],

			[ $r5, $r2, $r1, null, "undo conflicting edit" ],
			[ $r5, $r3, $r1, null, "undo two edits with conflict" ],
		];
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
		$content = $this->getHandler()->makeEmptyContent();
		$this->assertInstanceOf( EntityContent::class, $content );

		$this->setExpectedException( LogicException::class );
		$content->getEntity();
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

	/**
	 * @return Serializer
	 */
	protected function getEntitySerializer() {
		$newSerializerFactory = new SerializerFactory( new DataValueSerializer() );
		$newSerializer = $newSerializerFactory->newEntitySerializer();
		return $newSerializer;
	}

	public function exportTransformProvider() {
		$entity = $this->newEntity();

		$internalSerializer = WikibaseRepo::getDefaultInstance()->getStorageEntitySerializer();
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
		$newSerializer = $this->getEntitySerializer();
		$newBlob = json_encode( $newSerializer->serialize( $entity ) );

		return [
			'old serialization / ancient id format' => [ $veryVeryOldBlob, $newBlob ],
			'old serialization / new silly id format' => [ $veryOldBlob, $newBlob ],
			'old serialization / old serializer format' => [ $oldBlob, $newBlob ],
			'new serialization format, keep as is' => [ $newBlob, $newBlob ],
		];
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
		$entitySerializer = $this->getWikibaseRepo( $settings )->getStorageEntitySerializer();
		$expected = json_encode( $entitySerializer->serialize( $entity ) );

		$handler = $this->getHandler( $settings );
		$actual = $handler->exportTransform( $expected );

		$this->assertEquals( $expected, $actual );
	}

	public function testGetLegacyExportFormatDetector() {
		$detector = $this->getHandler()->getLegacyExportFormatDetector();

		if ( $detector === null ) {
			$this->markTestSkipped( 'handler has no legacy export format detector' );
		} else {
			$this->assertInternalType( 'callable', $detector );
		}
	}

	public function forCreationParamProvider() {
		return [
			[ true ],
			[ false ],
		];
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

	/**
	 * @param Title $title
	 * @return RequestContext
	 * @throws MWException
	 */
	protected function getContext( Title $title ) {
		$context = new RequestContext( new FauxRequest() );
		$context->setLanguage( 'qqx' );
		$context->setTitle( $title );

		return $context;
	}

	public function testShowMissingEntity() {
		$handler = $this->getHandler();

		$title = Title::makeTitle( $handler->getEntityNamespace(), 'MISSING' );

		$context = $this->getContext( $title );
		$handler->showMissingEntity( $title, $context );

		$this->assertContains( '(wikibase-noentity)', $context->getOutput()->getHTML() );
	}

	public function testSupportsSections() {
		$this->assertFalse( $this->getHandler()->supportsSections() );
	}

	public function testSupportsCategories() {
		$this->assertFalse( $this->getHandler()->supportsCategories() );
	}

	public function testSupportsRedirects() {
		$this->assertFalse( $this->getHandler()->supportsRedirects() );
	}

	public function testSupportsDirectEditing() {
		$this->assertFalse( $this->getHandler()->supportsDirectEditing() );
	}

	public function testSupportsDirectApiEditing() {
		$this->assertFalse( $this->getHandler()->supportsDirectApiEditing() );
	}

	public function testGetAutosummary() {
		$this->assertSame( '', $this->getHandler()->getAutosummary( null, null, 0 ) );
	}

	abstract protected function getExpectedSearchIndexFields();

	public function testFieldsForSearchIndex() {
		$handler = $this->getHandler();

		$searchEngine = $this->getMockBuilder( SearchEngine::class )->getMock();

		$searchEngine->expects( $this->any() )
			->method( 'makeSearchFieldMapping' )
			->will( $this->returnCallback( function ( $name, $type ) {
				return new DummySearchIndexFieldDefinition( $name, $type );
			} ) );

		$fields = $handler->getFieldsForSearchIndex( $searchEngine );
		foreach ( $this->getExpectedSearchIndexFields() as $expected ) {
			$this->assertInstanceOf( \SearchIndexField::class, $fields[$expected] );
			$mapping = $fields[$expected]->getMapping( $searchEngine );
			$this->assertEquals( $expected, $mapping['name'] );
		}
	}

	abstract protected function getTestContent();

	/**
	 * @param EntityHandler $handler
	 * @return PHPUnit_Framework_MockObject_MockObject|WikiPage
	 */
	protected function getMockWikiPage( EntityHandler $handler ) {
		$title = Title::makeTitle( $handler->getEntityNamespace(), "Asdflogjkasdefgo" );

		$page = $this->getMockBuilder( WikiPage::class )
			->setConstructorArgs( [ Title::newFromText( 'Q1' ) ] )
			->getMock();

		$page->method( 'getContent' )->willReturn( $this->getTestContent() );
		$page->method( 'getTitle' )->willReturn( $title );

		return $page;
	}

	abstract public function testDataForSearchIndex();

	public function testGetActionOverrides() {
		$handler = $this->getHandler();
		$overrides = $handler->getActionOverrides();

		foreach ( $overrides as $name => $classOrCallback ) {
			if ( is_string( $classOrCallback ) ) {
				$this->assertTrue(
					is_subclass_of( $classOrCallback, Action::class ),
					'Override for ' . $name . ' must be an action class, found ' . $classOrCallback
				);
			} elseif ( is_callable( $classOrCallback ) ) {
				// NOTE: for now, the callback must work with a WikiPage as well as an Article
				// object. Once I0335100b2 is merged, this is no longer needed.
				$wikiPage = $this->getMockWikiPage( $handler );
				$context = $this->getContext( $wikiPage->getTitle() );

				$action = $classOrCallback( $wikiPage, $context );
				$this->assertTrue(
					is_subclass_of( $action, Action::class ),
					'Callback for action ' . $name . ' must return an Action instance!'
				);

				$article = Article::newFromWikiPage( $wikiPage, $context );
				$action = $classOrCallback( $article, $context );
				$this->assertTrue(
					is_subclass_of( $action, Action::class ),
					'Callback for action ' . $name . ' must return an Action instance!'
				);
			} else {
				$this->fail( 'Expected a class name or callback as action override for ' . $name );
			}
		}
	}

}
