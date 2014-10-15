<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\EntityContent;
use Wikibase\ItemContent;
use Wikibase\Lib\Store\EntityRedirect;
use Wikibase\Repo\Content\ItemHandler;
use Wikibase\SettingsArray;

/**
 * @covers Wikibase\Repo\Content\ItemHandler
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseItem
 * @group WikibaseEntity
 * @group WikibaseEntityHandler
 *
 * @licence GNU GPL v2+
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
		return 'Wikibase\Repo\Content\ItemHandler';
	}

	/**
	 * @see EntityHandlerTest::contentProvider
	 */
	public function contentProvider() {
		$contents = parent::contentProvider();

		/**
		 * @var ItemContent $content
		 */
		$content = clone $contents[1][0];
		$content->getItem()->addSiteLink( new SiteLink( 'enwiki', 'Foobar' ) );
		$contents[] = array( $content );

		return $contents;
	}

	public function provideGetUndoContent() {
		$cases = parent::provideGetUndoContent();

		if ( !$this->getHandler()->supportsRedirects() ) {
			// As of 2014-06-30, redirects are still experimental.
			// So do a feature check before trying to test redirects.
			return $cases;
		}

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
	 * @param Entity $entity
	 *
	 * @return EntityContent
	 */
	protected function newEntityContent( Entity $entity = null ) {
		if ( !$entity ) {
			$entity = Item::newEmpty();
			$entity->setId( new ItemId( 'Q42' ) );
		}

		return $this->getHandler()->makeEntityContent( $entity );
	}

	public function testMakeEntityRedirectContent() {
		if ( !$this->getHandler()->supportsRedirects() ) {
			// As of 2014-06-30, redirects are still experimental.
			// So do a feature check before trying to test redirects.
			$this->markTestSkipped( 'Redirects not yet supported.' );
		}

		$q2 = new ItemId( 'Q2' );
		$q3 = new ItemId( 'Q3' );
		$redirect = new EntityRedirect( $q2, $q3 );

		$handler = $this->getHandler();
		$target = $handler->getTitleForId( $q3 );
		$content = $handler->makeEntityRedirectContent( $redirect );

		$this->assertEquals( $redirect, $content->getEntityRedirect() );
		$this->assertEquals( $target->getFullText(), $content->getRedirectTarget()->getFullText() );

		// getEntity() should fail
		$this->setExpectedException( 'MWException' );
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

		$item = Item::newEmpty();
		$item->setId( $id );
		return $item;
	}

	/**
	 * @param SettingsArray $settings
	 *
	 * @return ItemHandler
	 */
	protected function getHandler( SettingsArray $settings = null ) {
		$repo = $this->getRepo( $settings );
		return $repo->newItemHandler();
	}

}
