<?php

namespace Wikibase\Repo\Tests\Content;

use MWException;
use Title;
use Wikibase\Content\EntityInstanceHolder;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\EntityContent;
use Wikibase\ItemContent;
use Wikibase\Repo\Content\ItemHandler;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\SettingsArray;
use WikiPage;

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
		$contents = [];
		$contents[] = [ $this->newEntityContent() ];

		/** @var ItemContent $content */
		$content = $this->newEntityContent();
		$content->getEntity()->setAliases( 'en', [ 'foo' ] );
		$content->getEntity()->setDescription( 'de', 'foobar' );
		$content->getEntity()->setDescription( 'en', 'baz' );
		$content->getEntity()->setLabel( 'nl', 'o_O' );
		$contents[] = [ $content ];

		$content = $content->copy();
		$content->getItem()->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Foobar' );
		$contents[] = [ $content ];

		return $contents;
	}

	public function provideGetUndoContent() {
		$cases = parent::provideGetUndoContent();

		$item1 = $this->newEntity();
		$item1->setLabel( 'en', 'Foo' );

		$item2 = $this->newEntity();
		$item2->setLabel( 'en', 'Bar' );

		$itemContent1 = $this->newRedirectContent( $item1->getId(), new ItemId( 'Q112' ) );
		$itemContent2 = $this->newRedirectContent( $item1->getId(), new ItemId( 'Q113' ) );

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

	protected function getTestItemContent() {
		$item = new Item();
		$item->getFingerprint()->setLabel( 'en', 'Kitten' );
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Kitten' );
		$item->getStatements()->addNewStatement(
			new PropertyNoValueSnak( new PropertyId( 'P1' ) )
		);

		return ItemContent::newFromItem( $item );
	}

}
