<?php

namespace Wikibase\Repo\Tests\ParserOutput;

use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\TermList;
use Wikibase\LanguageFallbackChain;
use Wikibase\View\Termbox\Renderer\TermboxRenderer;
use Wikibase\View\TermboxView;

/**
 * @covers \Wikibase\Repo\ParserOutput\TermboxView
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

		$response = 'termbox says hi';

		$renderer = $this->newTermboxRenderer();
		$renderer->expects( $this->once() )
			->method( 'getContent' )
			->with( $entityId, $language )
			->willReturn( $response );

		$this->assertSame(
			$response,
			$this->newTermbox( $renderer )->getHtml(
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
			->willThrowException( new Exception( 'unspecific' ) );

		$this->assertSame(
			TermboxView::FALLBACK_HTML,
			$this->newTermbox( $renderer )->getHtml(
				$language,
				new TermList( [] ),
				new TermList( [] ),
				null,
				$entityId
			)
		);
	}

	/**
	 * @return TermboxRenderer|MockObject
	 */
	private function newTermboxRenderer(): TermboxRenderer {
		return $this->getMock( TermboxRenderer::class );
	}

	private function newTermbox( TermboxRenderer $renderer ): TermboxView {
		return new TermboxView(
			new LanguageFallbackChain( [] ),
			$renderer
		);
	}

}
