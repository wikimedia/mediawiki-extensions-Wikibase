<?php

namespace Wikibase\View\Tests;

use Language;
use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Repo\ParserOutput\PlaceholderEmittingEntityTermsView;
use Wikibase\View\EntityTermsViewFactory;
use Wikibase\View\LanguageDirectionalityLookup;
use Wikibase\View\Template\TemplateFactory;
use Wikibase\View\TermboxView;

/**
 * @covers \Wikibase\View\EntityTermsViewFactory
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityTermsViewFactoryTest extends TestCase {

	use PHPUnit4And6Compat;

	public function testNotRequestingTermbox_returnsPlaceHolderEmittingTermsView() {
		$factory = $this->newEntityTermsViewFactory();

		$termsView = $factory->newEntityTermsView(
			new Item( new ItemId( 'Q23' ) ),
			Language::factory( 'en' ),
			new LanguageFallbackChain( [] ),
			false
		);

		$this->assertInstanceOf(
			PlaceholderEmittingEntityTermsView::class,
			$termsView
		);
	}

	public function testRequestingTermbox_returnsTermboxView() {
		$factory = $this->newEntityTermsViewFactory();
		$termsView = $factory->newEntityTermsView(
			new Item( new ItemId( 'Q23' ) ),
			Language::factory( 'en' ),
			new LanguageFallbackChain( [] ),
			true
		);

		$this->assertInstanceOf( TermboxView::class, $termsView );
	}

	private function newEntityTermsViewFactory() : EntityTermsViewFactory {
		return new EntityTermsViewFactory(
			$this->createMock( LanguageDirectionalityLookup::class ),
			$this->createMock( LanguageNameLookup::class ),
			$this->createMock( TemplateFactory::class )
		);
	}

}
