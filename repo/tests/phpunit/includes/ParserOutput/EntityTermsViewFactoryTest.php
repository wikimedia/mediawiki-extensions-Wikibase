<?php

namespace Wikibase\Repo\Tests\ParserOutput;

use Language;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\LanguageFallbackChain;
use Wikibase\Repo\ParserOutput\EntityTermsViewFactory;
use Wikibase\Repo\ParserOutput\PlaceholderEmittingEntityTermsView;

/**
 * @covers \Wikibase\Repo\ParserOutput\EntityTermsViewFactory
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityTermsViewFactoryTest extends TestCase {

	public function testCreatesNewTermsView() {
		$termsView = $this->newEntityTermsViewFactory()
			->newEntityTermsView(
				new Item( new ItemId( 'Q42' ) ),
				Language::factory( 'en' ),
				new LanguageFallbackChain( [] )
			);

		$this->assertInstanceOf( PlaceholderEmittingEntityTermsView::class, $termsView );
	}

	private function newEntityTermsViewFactory() {
		return new EntityTermsViewFactory();
	}

}
