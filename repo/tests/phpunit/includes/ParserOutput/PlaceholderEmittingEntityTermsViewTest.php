<?php

namespace Wikibase\Repo\Tests\ParserOutput;

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
 * @covers \Wikibase\Repo\ParserOutput\PlaceholderEmittingEntityTermsView
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class PlaceholderEmittingEntityTermsViewTest extends \PHPUnit\Framework\TestCase {

	private function newEntityTermsView( TextInjector $textInjector ) {
		$termsListView = $this->createMock( TermsListView::class );

		return new PlaceholderEmittingEntityTermsView(
			$this->createMock( HtmlTermRenderer::class ),
			$this->createMock( LabelDescriptionLookup::class ),
			TemplateFactory::getDefaultInstance(),
			$this->createMock( EditSectionGenerator::class ),
			$this->createMock( LocalizedTextProvider::class ),
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
			$this->assertStringContainsString( $marker, $html );
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
