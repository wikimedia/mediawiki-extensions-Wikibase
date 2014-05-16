<?php

namespace Wikibase\Test;

use ContentHandler;
use Language;
use Revision;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Entity;
use Wikibase\EntityContent;
use Wikibase\EntityHandler;
use Wikibase\Lib\Store\EntityRedirect;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\EntityHandler
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
	 * @return EntityHandler
	 */
	protected function getHandler() {
		return ContentHandler::getForModelID( $this->getModelId() );
	}

	/**
	 * @param Entity $entity
	 *
	 * @return EntityContent
	 */
	protected abstract function newEntityContent( Entity $entity = null );

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
	 * @return Entity
	 */
	protected function newEntity() {
		return $this->newEntityContent()->getEntity();
	}

	/**
	 * Returns EntityContents that can be handled by the EntityHandler deriving class.
	 * @return array
	 */
	public function contentProvider() {
		/**
		 * @var EntityContent $content
		 */
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
	 * @param \Wikibase\EntityHandler $entityHandler
	 */
	public function testGetModelName( EntityHandler $entityHandler ) {
		$this->assertEquals( $this->getModelId(), $entityHandler->getModelID() );
		$this->assertInstanceOf( '\ContentHandler', $entityHandler );
		$this->assertInstanceOf( $this->getClassName(), $entityHandler );
	}


	/**
	 * @dataProvider instanceProvider
	 * @param \Wikibase\EntityHandler $entityHandler
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
		$title = \Title::makeTitle( $handler->getEntityNamespace(), "1234567" );

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
			$this->assertEquals( $expected->getNativeData(), $undo->getNativeData(), $message );
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

}
