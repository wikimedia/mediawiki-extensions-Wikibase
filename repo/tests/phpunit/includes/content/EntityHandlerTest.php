<?php

namespace Wikibase\Test;

use ContentHandler;
use DataValues\Serializers\DataValueSerializer;
use Language;
use Revision;
use RuntimeException;
use Title;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\EntityContent;
use Wikibase\InternalSerialization\SerializerFactory;
use Wikibase\Lib\Serializers\LegacyInternalEntitySerializer;
use Wikibase\Lib\Store\EntityRedirect;
use Wikibase\Repo\Content\EntityHandler;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\SettingsArray;

/**
 * @covers Wikibase\Repo\Content\EntityHandler
 *
 * @group Wikibase
 * @group WikibaseEntity
 * @group WikibaseEntityHandler
 * @group WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
abstract class EntityHandlerTest extends \MediaWikiTestCase {

	abstract public function getClassName();

	abstract public function getModelId();

	/**
	 * Returns instances of the EntityHandler deriving class.
	 * @return array
	 */
	public function instanceProvider() {
		return array(
			array( $this->getHandler() ),
		);
	}

	/**
	 * @param SettingsArray|null $settings
	 *
	 * @return WikibaseRepo
	 */
	protected function getRepo( SettingsArray $settings = null ) {
		$repoSettings = WikibaseRepo::getDefaultInstance()->getSettings()->getArrayCopy();

		if ( $settings ) {
			$repoSettings = array_merge( $repoSettings, $settings->getArrayCopy() );
		}

		return new WikibaseRepo( new SettingsArray( $repoSettings ) );
	}

	/**
	 * @param SettingsArray|null $settings
	 *
	 * @return EntityHandler
	 */
	protected abstract function getHandler( SettingsArray $settings = null );

	/**
	 * @param Entity|null $entity
	 *
	 * @return EntityContent
	 */
	protected function newEntityContent( Entity $entity = null ) {
		if ( !$entity ) {
			$entity = $this->newEntity();
		}

		$handler = $this->getHandler();
		return $handler->makeEntityContent( $entity );
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
	 * @return Entity
	 */
	protected abstract function newEntity( EntityId $id = null );

	/**
	 * Returns EntityContents that can be handled by the EntityHandler deriving class.
	 *
	 * @return array[]
	 */
	public function contentProvider() {
		$content = $this->newEntityContent();
		$content->getEntity()->addAliases( 'en', array( 'foo' ) );
		$content->getEntity()->setDescription( 'de', 'foobar' );
		$content->getEntity()->setDescription( 'en', 'baz' );
		$content->getEntity()->setLabel( 'nl', 'o_O' );

		return array(
			array( $this->newEntityContent() ),
			array( $content ),
		);
	}

	/**
	 * @dataProvider instanceProvider
	 * @param EntityHandler $entityHandler
	 */
	public function testGetModelName( EntityHandler $entityHandler ) {
		$this->assertEquals( $this->getModelId(), $entityHandler->getModelID() );
		$this->assertInstanceOf( 'ContentHandler', $entityHandler );
		$this->assertInstanceOf( $this->getClassName(), $entityHandler );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param EntityHandler $entityHandler
	 */
	public function testGetSpecialPageForCreation( EntityHandler $entityHandler ) {
		$specialPageName = $entityHandler->getSpecialPageForCreation();
		$this->assertTrue( $specialPageName === null || is_string( $specialPageName ) );
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
		$e1 = $this->newEntity();
		$r1 = $this->fakeRevision( $this->newEntityContent( $e1 ), 1 );

		$e2 = $e1->copy();
		$e2->setLabel( 'en', 'Foo' );
		$r2 = $this->fakeRevision( $this->newEntityContent( $e2 ), 2 );

		$e3 = $e2->copy();
		$e3->setLabel( 'de', 'Fuh' );
		$r3 = $this->fakeRevision( $this->newEntityContent( $e3 ), 3 );

		$e4 = $e3->copy();
		$e4->setLabel( 'fr', 'Fu' );
		$r4 = $this->fakeRevision( $this->newEntityContent( $e4 ), 4 );

		$e5 = $e4->copy();
		$e5->setLabel( 'en', 'F00' );
		$r5 = $this->fakeRevision( $this->newEntityContent( $e5 ), 5 );

		$e5u4 = $e5->copy();
		$e5u4->removeLabel( 'fr' );

		$e5u4u3 = $e5u4->copy();
		$e5u4u3->removeLabel( 'de' );

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
	 * @param EntityContent $expected
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
			$this->assertInstanceOf( 'Wikibase\EntityContent', $undo, $message );
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
		$content = $handler->makeEntityContent( $entity );

		$this->assertEquals( $this->getModelId(), $content->getModel() );
		$this->assertSame( $entity, $content->getEntity() );
	}

	public function testMakeEmptyContent() {
		// We don't support empty content.
		$this->setExpectedException( 'MWException' );

		$handler = $this->getHandler();
		$handler->makeEmptyContent();
	}

	public function testMakeRedirectContent() {
		// We don't support title based redirects.
		$this->setExpectedException( 'MWException' );

		$handler = $this->getHandler();
		$handler->makeRedirectContent( Title::newFromText( 'X11', $handler->getEntityNamespace() ) );
	}

	public function testMakeEmptyEntity() {
		$handler = $this->getHandler();
		$entity = $handler->makeEmptyEntity();

		$this->assertEquals( $handler->getEntityType(), $entity->getType(), 'entity type' );
	}

	public abstract function entityIdProvider();

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

		$legacySerializer = new LegacyInternalEntitySerializer();
		$oldBlob = json_encode( $legacySerializer->serialize( $entity ) );

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
		$settings->setSetting( 'internalEntitySerializerClass', null );

		$handler = $this->getHandler( $settings );
		$actual = $handler->exportTransform( $blob );

		$this->assertEquals( $expected, $actual );
	}

	public function testExportTransform_neverRecodeNonLegacyFormat() {
		$codec = $this->getMockBuilder( 'Wikibase\Lib\Store\EntityContentDataCodec' )
			->disableOriginalConstructor()
			->getMock();
		$codec->expects( $this->never() )
			->method( 'decodeEntity' );
		$codec->expects( $this->never() )
			->method( 'encodeEntity' );

		$settings = new SettingsArray();
		$settings->setSetting( 'transformLegacyFormatOnExport', true );
		$settings->setSetting( 'internalEntitySerializerClass', null );

		$entity = $this->newEntity();
		$currentSerializer = $this->getRepo( $settings )->getInternalEntitySerializer();
		$expected = json_encode( $currentSerializer->serialize( $entity ) );

		$handler = $this->getHandler( $settings, $codec );
		$actual = $handler->exportTransform( $expected );

		$this->assertEquals( $expected, $actual );
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
			$this->assertInstanceOf( 'Wikibase\Validators\EntityValidator', $validator );
		}
	}

}
