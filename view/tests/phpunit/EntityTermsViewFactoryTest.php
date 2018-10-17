<?php

namespace Wikibase\View\Tests;

use Language;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookupFactory;
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

	/** @var LanguageFallbackLabelDescriptionLookupFactory|MockObject */
	private $labelDescriptionLookupFactory;

	public function setUp() {
		parent::setUp();

		$this->labelDescriptionLookupFactory = $this->createMock(
			LanguageFallbackLabelDescriptionLookupFactory::class
		);
	}

	public function testNotRequestingTermbox_returnsPlaceHolderEmittingTermsView() {
		$factory = $this->newEntityTermsViewFactory();
		$this->labelDescriptionLookupFactory->expects( $this->once() )
			->method( 'newLabelDescriptionLookup' )
			->willReturn( $this->createMock( LabelDescriptionLookup::class ) );

		$termsView = $factory->newEntityTermsView( Language::factory( 'en' ), false );

		$this->assertInstanceOf(
			PlaceholderEmittingEntityTermsView::class,
			$termsView
		);
	}

	public function testRequestingTermbox_returnsTermboxView() {
		$factory = $this->newEntityTermsViewFactory();
		$termsView = $factory->newEntityTermsView( Language::factory( 'en' ), true );
		$this->assertInstanceOf( TermboxView::class, $termsView );
	}

	private function newEntityTermsViewFactory() : EntityTermsViewFactory {
		return new EntityTermsViewFactory(
			$this->createMock( LanguageDirectionalityLookup::class ),
			$this->createMock( LanguageNameLookup::class ),
			$this->labelDescriptionLookupFactory,
			$this->createMock( TemplateFactory::class )
		);
	}

}
