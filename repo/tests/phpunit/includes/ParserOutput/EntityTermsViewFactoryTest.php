<?php

namespace Wikibase\Repo\Tests\ParserOutput;

use MediaWiki\MediaWikiServices;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\TermLanguageFallbackChain;
use Wikibase\Repo\ParserOutput\EntityTermsViewFactory;
use Wikibase\Repo\ParserOutput\PlaceholderEmittingEntityTermsView;
use Wikibase\Repo\ParserOutput\TermboxView;

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
				MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'en' ),
				new TermLanguageFallbackChain( [], $this->createStub( ContentLanguages::class ) )
			);

		$this->assertInstanceOf( PlaceholderEmittingEntityTermsView::class, $termsView );
	}

	public function testGivenShouldRenderTermbox_returnsTermboxView() {
		$termsView = $this->newEntityTermsViewFactory()
			->newEntityTermsView(
				new Item( new ItemId( 'Q42' ) ),
				MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'en' ),
				new TermLanguageFallbackChain( [], $this->createStub( ContentLanguages::class ) ),
				true
			);

		$this->assertInstanceOf( TermboxView::class, $termsView );
	}

	private function newEntityTermsViewFactory() {
		return new EntityTermsViewFactory();
	}

}
