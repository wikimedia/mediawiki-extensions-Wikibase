<?php

namespace Wikibase\View\Tests\Termbox;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\TermList;
use Wikibase\LanguageFallbackChain;
use Wikibase\View\EntityTermsView;
use Wikibase\View\LocalizedTextProvider;
use Wikibase\View\SpecialPageLinker;
use Wikibase\View\Termbox\Renderer\TermboxRenderer;
use Wikibase\View\Termbox\Renderer\TermboxRenderingException;
use Wikibase\View\Termbox\TermboxView;

/**
 * @covers \Wikibase\View\Termbox\TermboxView
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TermboxViewTest extends TestCase {

	use PHPUnit4And6Compat;

	public function testGetHtmlWithClientStringResponse_returnsContent() {
		$language = 'en';
		$entityId = new ItemId( 'Q42' );
		$editLinkUrl = '/edit/Q42';
		$fallbackChain = $this->createMock( LanguageFallbackChain::class );

		$response = 'termbox says hi';

		$renderer = $this->newTermboxRenderer();
		$renderer->expects( $this->once() )
			->method( 'getContent' )
			->with( $entityId, $language, $editLinkUrl, $fallbackChain )
			->willReturn( $response );

		$this->assertSame(
			$response,
			$this->newTermbox(
				$renderer,
				$this->newLocalizedTextProvider(),
				$this->newLinkingSpecialPageLinker( $entityId, $editLinkUrl ),
				$fallbackChain
			)->getHtml(
				$language,
				new TermList( [] ),
				new TermList( [] ),
				null,
				$entityId
			)
		);
	}

	public function testGetHtmlWithClientThrowingException_returnsFallbackContent() {
		$language = 'en';
		$entityId = new ItemId( 'Q42' );

		$renderer = $this->newTermboxRenderer();
		$renderer->expects( $this->once() )
			->method( 'getContent' )
			->willThrowException( new TermboxRenderingException( 'specific reason of failure' ) );

		$this->assertSame(
			TermboxView::FALLBACK_HTML,
			$this->newTermbox( $renderer, $this->newLocalizedTextProvider(), $this->newSpecialPageLinker() )->getHtml(
				$language,
				new TermList( [] ),
				new TermList( [] ),
				null,
				$entityId
			)
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

	public function testGetPlaceholders_returnsNone() {
		$entity = $this->getMock( EntityDocument::class );
		$languageCode = 'en';

		$termbox = $this->newTermbox(
			$this->newTermboxRenderer(),
			$this->newLocalizedTextProvider(),
			$this->newSpecialPageLinker()
		);

		$this->assertSame(
			[],
			$termbox->getPlaceholders( $entity, $languageCode )
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
		LanguageFallbackChain $fallbackChain = null
	): TermboxView {
		return new TermboxView(
			$fallbackChain ?: new LanguageFallbackChain( [] ),
			$renderer,
			$textProvider,
			$specialPageLinker
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
