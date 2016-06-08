<?php

namespace Wikibase\Test;

use MWException;
use Wikibase\Content\EntityInstanceHolder;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\EntityContent;
use Wikibase\ItemContent;
use Wikibase\Repo\Content\ItemHandler;
use Wikibase\SettingsArray;

/**
 * @covers Wikibase\Repo\Content\ItemHandler
 * @covers Wikibase\Repo\Content\EntityHandler
 *
 * @group Wikibase
 * @group WikibaseRepo
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

	/**
	 * @see EntityHandlerTest::getClassName
	 * @return string
	 */
	public function getClassName() {
		return ItemHandler::class;
	}

	/**
	 * @see EntityHandlerTest::contentProvider
	 */
	public function contentProvider() {
		$contents = parent::contentProvider();

		/** @var ItemContent $content */
		$content = $this->newEntityContent();
		$content->getEntity()->setAliases( 'en', array( 'foo' ) );
		$content->getEntity()->setDescription( 'de', 'foobar' );
		$content->getEntity()->setDescription( 'en', 'baz' );
		$content->getEntity()->setLabel( 'nl', 'o_O' );
		$contents[] = array( $content );

		$content = $content->copy();
		$content->getItem()->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Foobar' );
		$contents[] = array( $content );

		return $contents;
	}

	public function provideGetUndoContent() {
		$cases = parent::provideGetUndoContent();

		$e1 = $this->newEntity();
		$e1->setLabel( 'en', 'Foo' );
		$r1 = $this->fakeRevision( $this->newEntityContent( $e1 ), 11 );

		$e2 = $this->newRedirectContent( $e1->getId(), new ItemId( 'Q112' ) );
		$r2 = $this->fakeRevision( $e2, 12 );

		$e3 = $this->newRedirectContent( $e1->getId(), new ItemId( 'Q113' ) );
		$r3 = $this->fakeRevision( $e3, 13 );

		$e4 = $this->newEntity();
		$e4->setLabel( 'en', 'Bar' );
		$r4 = $this->fakeRevision( $this->newEntityContent( $e4 ), 14 );

		$cases[] = array( $r2, $r2, $r1, $this->newEntityContent( $e1 ), "undo redirect" );
		$cases[] = array( $r3, $r3, $r2, $e2, "undo redirect change" );
		$cases[] = array( $r3, $r2, $r1, null, "undo redirect conflict" );
		$cases[] = array( $r4, $r4, $r3, $e3, "redo redirect" );

		return $cases;
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
		return array(
			array( 'Q7' ),
		);
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

}
