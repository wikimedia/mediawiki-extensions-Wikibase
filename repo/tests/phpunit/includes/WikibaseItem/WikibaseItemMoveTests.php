<?php

/**
 * Tests prevention of moving pages in and out of the data NS.
 *
 * @file
 * @since 0.1
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author John Erling Blad < jeblad@gmail.com >
 *
 */
class WikibaseItemTests extends MediaWikiTestCase {

	/**
	 * @var WikibaseItem
	 */
	protected $item;

	/**
	 * @var WikiPage
	 */
	protected $page;

	/**
	 * This is to set up the environment
	 */
	public function setUp() {
		parent::setUp();

		$this->item = WikibaseItem::newEmpty();
		$this->item->save();

		$title = Title::newFromText( 'wbmovetest' );
		$this->page =  new WikiPage( $title );
		$this->page->doEditContent( 'foobar', 'test' );
	}

	/**
	 * This is to tear down the environment
	 */
	public function tearDown() {
		parent::tearDown();
	}

	/**
	 * Tests @see WikibaseItem::getIdForSiteLink
	 */
	public function testMovePrevention() {
		// Moving a regular page into data NS onto an existing item
		$this->assertFalse( $this->page->getTitle()->moveTo( $this->item->getTitle() ) === true );

		// Moving a regular page into data NS to an invalid location
		$this->assertFalse( $this->page->getTitle()->moveTo(
			Title::newFromText( $this->page->getTitle()->getText(), WB_NS_DATA )
		) === true );

		// Moving a regular page into data NS to an empty (but valid) location
		$this->assertFalse( $this->page->getTitle()->moveTo(
			WikibaseItem::newFromArray( array( 'entity' => 'q42' ) )->getTitle()
		) === true );

		// Moving item page out of data NS onto an existing page
		$this->assertFalse( $this->item->getTitle()->moveTo( $this->page->getTitle() ) === true );

		// Moving item page out of data NS onto a non-existing page
		$this->assertFalse( $this->item->getTitle()->moveTo( Title::newFromText( 'wbmovetestitem' ) ) === true );

		// Moving item to an invalid location in the data NS
		$this->assertFalse( $this->item->getTitle()->moveTo(
			Title::newFromText( $this->page->getTitle()->getText(), WB_NS_DATA )
		) === true );

		// Moving item to an valid location in the data NS
		$this->assertFalse( $this->item->getTitle()->moveTo(
			WikibaseItem::newFromArray( array( 'entity' => 'q42' ) )->getTitle()
		) === true );
	}

}
	