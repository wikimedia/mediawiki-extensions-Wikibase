<?php

namespace Wikibase\Test;

use InvalidArgumentException;
use Language;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\EntityRevision;
use Wikibase\Repo\View\ItemView;

/**
 * @covers Wikibase\Repo\View\ItemView
 *
 * @group Wikibase
 * @group WikibaseItemView
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class ItemViewTest extends \PHPUnit_Framework_TestCase {

	public function provideEntityRevisions() {
		$revisions = array();
		$revId = 1234;
		$timestamp = wfTimestamp( TS_MW );

		$item = Item::newEmpty();
		$revisions['new item'] = array( new EntityRevision( $item, $revId++, $timestamp ) );

		$item = $item->copy();
		$item->setId( 123 );
		$revisions['existing item'] = array( new EntityRevision( $item, $revId++, $timestamp ) );

		return $revisions;
	}

	/**
	 * @dataProvider provideEntityRevisions
	 *
	 * @param EntityRevision $entityRevision
	 */
	public function testGetHtml( EntityRevision $entityRevision ) {
		$itemView = $this->getItemView();
		$html = $itemView->getHtml( $entityRevision );
		$id = $entityRevision->getEntity()->getId();

		if ( $id === null ) {
			$this->assertContains( 'new', $html, "Html for new items should cotain keyword 'new'" );
		} else {
			$this->assertContains( $id->getSerialization(), $html, "Html for existing items should contain the serialized id" );
		}

		$this->assertContains( Item::ENTITY_TYPE, $html, "Html should contain item type" );

		foreach ( array( '<fingerprint>', '<claims>', '<sitelinks>' ) as $htmlSnippet ) {
			$this->assertContains( $htmlSnippet, $html, "Generated html should contain '$htmlSnippet'" );
		}

		foreach ( array( 'wikipedia', 'special' ) as $siteLinkGroup ) {
			$this->assertContains( $siteLinkGroup, $html, "Generated html should contain $siteLinkGroup site link group" );
		}

		$placeholders = $itemView->getPlaceholders();
		$placeholderNames = array();
		foreach ( $placeholders as $placeholder => $args ) {
			$placeholderNames[] = $args[0];
			$this->assertContains( $placeholder, $html, "Generated html should contain placeholder for key '{$args[0]}'" );
		}

		if ( $id !== null ) {
			foreach ( array( 'termbox', 'termbox-toc' ) as $placeholderName ) {
				$this->assertContains( $placeholderName, $placeholderNames, "Placeholder '$placeholderName' should be used" );
			}
		}
	}

	/**
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage $entityRevision must contain an Item.
	 */
	public function testGetHtmlFailsForWrongEntityType() {
		$itemView = $this->getItemView();
		$revision = new EntityRevision( Property::newEmpty(), 1234, wfTimestamp( TS_MW ) );

		$itemView->getHtml( $revision );
	}

	private function getItemView() {
		return new ItemView(
			$this->getViewMock( 'Wikibase\Repo\View\FingerprintView', '<fingerprint>' ),
			$this->getViewMock( 'Wikibase\Repo\View\ClaimsView', '<claims>' ),
			$this->getViewMock( 'Wikibase\Repo\View\SiteLinksView', '<sitelinks>' ),
			array( 'wikipedia', 'special' ),
			Language::factory( 'en' )
		);
	}

	private function getViewMock( $className, $returnValue ) {
		$view = $this->getMockBuilder( $className )
			->disableOriginalConstructor()
			->getMock();

		$view->expects( $this->any() )
			->method( 'getHtml' )
			->will( $this->returnValue( $returnValue ) );

		return $view;
	}

}
