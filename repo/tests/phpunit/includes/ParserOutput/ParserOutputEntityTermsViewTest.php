<?php

namespace Wikibase\Repo\Tests\ParserOutput;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Repo\ParserOutput\ParserOutputEntityTermsView;
use Wikibase\Repo\ParserOutput\TextInjector;
use Wikibase\View\EditSectionGenerator;
use Wikibase\View\LocalizedTextProvider;
use Wikibase\View\Template\TemplateFactory;

/**
 * @covers Wikibase\Repo\ParserOutput\ParserOutputEntityTermsView
 *
 * @license GNU GPL v2+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class ParserOutputEntityTermsViewTest extends PHPUnit_Framework_TestCase {

	private function newEntityTermsView( TextInjector $textInjector ) {
		return new ParserOutputEntityTermsView(
			TemplateFactory::getDefaultInstance(),
			$this->getMock( EditSectionGenerator::class ),
			$this->getMock( LocalizedTextProvider::class ),
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

}
