<?php

namespace Wikibase\Repo\Tests\ParserOutput;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Repo\ParserOutput\PlaceholderEmittingEntityTermsView;
use Wikibase\Repo\ParserOutput\TextInjector;
use Wikibase\View\EditSectionGenerator;
use Wikibase\View\LocalizedTextProvider;
use Wikibase\View\Template\TemplateFactory;
use Wikibase\View\TermsListView;

/**
 * @covers Wikibase\Repo\ParserOutput\PlaceholderEmittingEntityTermsView
 *
 * @license GNU GPL v2+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class PlaceholderEmittingEntityTermsViewTest extends PHPUnit_Framework_TestCase {

	private function newEntityTermsView( TextInjector $textInjector ) {
		$termsListView = $this->getMockBuilder( TermsListView::class )
			->disableOriginalConstructor()
			->getMock();

		return new PlaceholderEmittingEntityTermsView(
			TemplateFactory::getDefaultInstance(),
			$this->getMock( EditSectionGenerator::class ),
			$this->getMock( LocalizedTextProvider::class ),
			$termsListView,
			$textInjector
		);
	}

	public function testGetHtml() {
		$textInjector = new TextInjector();
		$property = new Property( null, null, 'string' );

		$entityTermsView = $this->newEntityTermsView( $textInjector );

		$result = $entityTermsView->getHtml(
			'lkt',
			$property,
			$property,
			$property
		);

		$this->assertEquals(
			array_values( $textInjector->getMarkers() ),
			[ [ 'entityViewPlaceholder-entitytermsview-entitytermsforlanguagelistview-class' ], [ 'termbox' ] ]
		);
	}

	public function testGetTermsListItems() {
		$textInjector = new TextInjector();
		$property = new Property( null, null, 'string' );

		$entityTermsView = $this->newEntityTermsView( $textInjector );

		$entityTermsView->getHtml(
			'lkt',
			$property,
			$property,
			$property
		);
		$termsListItems = $entityTermsView->getTermsListItems();

		$this->assertEquals(
			$termsListItems,
			[ 'lkt' => null ]
		);
	}

}
