<?php

namespace Wikibase\View\Tests\Termbox;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\TermList;
use Wikibase\LanguageFallbackChain;
use Wikibase\Repo\ParserOutput\TextInjector;
use Wikibase\View\EntityTermsView;
use Wikibase\View\LocalizedTextProvider;
use Wikibase\View\SpecialPageLinker;
use Wikibase\View\Termbox\Renderer\TermboxRenderer;
use Wikibase\View\Termbox\Renderer\TermboxRenderingException;
use Wikibase\Repo\ParserOutput\TermboxView;

/**
 * @covers \Wikibase\Repo\ParserOutput\TermboxView
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TermboxViewTest extends TestCase {

	use PHPUnit4And6Compat;

	public function testGetHtmlReturnsPlaceholderMarker() {
		$marker = 'termbox-marker';
		$textInjector = $this->createMock( TextInjector::class );
		$textInjector->expects( $this->once() )
			->method( 'newMarker' )
			->with( TermboxView::TERMBOX_PLACEHOLDER )
			->willReturn( $marker );

		$termbox = new TermboxView(
			$this->createMock( LanguageFallbackChain::class ),
			$this->newTermboxRenderer(),
			$this->newLocalizedTextProvider(),
			$this->newSpecialPageLinker(),
			$textInjector
		);

		$this->assertSame( $marker, $termbox->getHtml(
			'en',
			new TermList( [] ),
			new TermList( [] ),
			null,
			new ItemId( 'Q42' )
		) );
	}

	public function testGetPlaceholdersContainsMarkers() {
		$markers = [ 'marker' => 'marker-name' ];

		$textInjector = $this->createMock( TextInjector::class );
		$textInjector->expects( $this->once() )
			->method( 'getMarkers' )
			->willReturn( $markers );

		$termbox = new TermboxView(
			$this->createMock( LanguageFallbackChain::class ),
			$this->newTermboxRenderer(),
			$this->newLocalizedTextProvider(),
			$this->newSpecialPageLinker(),
			$textInjector
		);

		$this->assertContains( $markers, $termbox->getPlaceholders( new Item( new ItemId( 'Q123' ) ), 'en' ) );
	}

	public function testPlaceHolderWithMarkupWithClientStringResponse_returnsContent() {
		$language = 'en';
		$item = new Item( new ItemId( 'Q42' ) );
		$editLinkUrl = '/edit/Q42';
		$fallbackChain = $this->createMock( LanguageFallbackChain::class );

		$response = 'termbox says hi';

		$renderer = $this->newTermboxRenderer();
		$renderer->expects( $this->once() )
			->method( 'getContent' )
			->with( $item->getId(), $language, $editLinkUrl, $fallbackChain )
			->willReturn( $response );

		$placeholders = $this->newTermbox(
			$renderer,
			$this->newLocalizedTextProvider(),
			$this->newLinkingSpecialPageLinker( $item->getId(), $editLinkUrl ),
			$fallbackChain
		)->getPlaceholders(
			$item,
			$language
		);

		$this->assertSame(
			$response,
			$placeholders[ TermboxView::TERMBOX_MARKUP_BLOB ]
		);
	}

	public function testPlaceHolderWithMarkupWithClientThrowingException_returnsFallbackContent() {
		$language = 'en';
		$item = new Item( new ItemId( 'Q42' ) );

		$renderer = $this->newTermboxRenderer();
		$renderer->expects( $this->once() )
			->method( 'getContent' )
			->willThrowException( new TermboxRenderingException( 'specific reason of failure' ) );

		$placeholders = $this->newTermbox( $renderer, $this->newLocalizedTextProvider(), $this->newSpecialPageLinker() )->getPlaceholders(
			$item,
			$language
		);
		$this->assertSame(
			TermboxView::FALLBACK_HTML,
			$placeholders[ TermboxView::TERMBOX_MARKUP_BLOB ]
		);
	}

	public function testGetTitleHtml_returnsHtmlWithEntityId() {
		$entityId = new ItemId( 'Q42' );
		$decoratedIdSerialization = '( ' . $entityId->getSerialization() . ')';

		$textProvider = $this->newLocalizedTextProvider();
		$textProvider->method( 'get' )
			->with( 'parentheses', [ $entityId->getSerialization() ] )
			->willReturn( $decoratedIdSerialization );

		$termbox = $this->newTermbox(
			$this->newTermboxRenderer(),
			$textProvider,
			$this->newSpecialPageLinker()
		);

		$this->assertSame(
			$decoratedIdSerialization,
			$termbox->getTitleHtml( $entityId )
		);
	}

	/**
	 * @return TermboxRenderer|MockObject
	 */
	private function newTermboxRenderer(): TermboxRenderer {
		return $this->getMock( TermboxRenderer::class );
	}

	/**
	 * @return LocalizedTextProvider|MockObject
	 */
	private function newLocalizedTextProvider(): LocalizedTextProvider {
		return $this->getMock( LocalizedTextProvider::class );
	}

	private function newTermbox(
		TermboxRenderer $renderer,
		LocalizedTextProvider $textProvider,
		SpecialPageLinker $specialPageLinker,
		LanguageFallbackChain $fallbackChain = null,
		TextInjector $textInjector = null
	): TermboxView {
		return new TermboxView(
			$fallbackChain ?: new LanguageFallbackChain( [] ),
			$renderer,
			$textProvider,
			$specialPageLinker,
			$textInjector ?: new TextInjector()
		);
	}

	private function newLinkingSpecialPageLinker( $itemId, $editLinkUrl ) {
		$specialPageLinker = $this->newSpecialPageLinker();
		$specialPageLinker->expects( $this->once() )
			->method( 'getLink' )
			->with( EntityTermsView::TERMS_EDIT_SPECIAL_PAGE, [ $itemId ] )
			->willReturn( $editLinkUrl );
		return $specialPageLinker;
	}

	/**
	 * @return MockObject|SpecialPageLinker
	 */
	private function newSpecialPageLinker() {
		return $this->createMock( SpecialPageLinker::class );
	}

}
