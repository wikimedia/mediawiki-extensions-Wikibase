<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SimpleSiteLink;
use Wikibase\ItemContent;

/**
 * @covers Wikibase\ItemHandler
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
		return '\Wikibase\ItemHandler';
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
		$content->getItem()->addSiteLink( new SimpleSiteLink( 'enwiki', 'Foobar' ) );
		$contents[] = array( $content );

		return $contents;
	}

	public function provideGetUndoContent() {
		$cases = parent::provideGetUndoContent();

		$e1 = $this->newEntity();
		$e1->setLabel( 'en', 'Foo' );
		$r1 = $this->fakeRevision( $this->newEntityContent( $e1 ), 11 );

		$e2 = $this->newRedirectContent( new ItemId( 'Q112' ) );
		$r2 = $this->fakeRevision( $e2, 12 );

		$e3 = $this->newRedirectContent( new ItemId( 'Q113' ) );
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

}
