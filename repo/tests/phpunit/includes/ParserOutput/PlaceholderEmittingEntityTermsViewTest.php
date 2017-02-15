<?php

namespace Wikibase\Repo\Tests\ParserOutput;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\ParserOutput\PlaceholderEmittingEntityTermsView;
use Wikibase\Repo\ParserOutput\TextInjector;
use Wikibase\View\EditSectionGenerator;
use Wikibase\View\HtmlTermRenderer;
use Wikibase\View\LocalizedTextProvider;
use Wikibase\View\Template\TemplateFactory;
use Wikibase\View\TermsListView;

/**
 * @covers Wikibase\Repo\ParserOutput\PlaceholderEmittingEntityTermsView
 *
 * @group Wikibase
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
			$this->getMock( HtmlTermRenderer::class ),
			$this->getMock( LabelDescriptionLookup::class ),
			TemplateFactory::getDefaultInstance(),
			$this->getMock( EditSectionGenerator::class ),
			$this->getMock( LocalizedTextProvider::class ),
			$termsListView,
			$textInjector
		);
	}

	public function testGetHtml() {
		$textInjector = new TextInjector();

		$entityTermsView = $this->newEntityTermsView( $textInjector );

		$html = $entityTermsView->getHtml( 'lkt', new TermList(), new TermList() );
		$markers = $textInjector->getMarkers();

		foreach ( $markers as $marker => $name ) {
			$this->assertContains( $marker, $html );
		}

		$this->assertSame(
			[
				[ 'entityViewPlaceholder-entitytermsview-entitytermsforlanguagelistview-class' ],
				[ 'termbox' ],
			],
			array_values( $markers )
		);
	}

	public function testGetTermsListItems() {
		$textInjector = new TextInjector();

		$entityTermsView = $this->newEntityTermsView( $textInjector );

		$termsListItems = $entityTermsView->getTermsListItems( 'lkt', new TermList(), new TermList() );

		$this->assertSame( [ 'lkt' => null ], $termsListItems );
	}

}
