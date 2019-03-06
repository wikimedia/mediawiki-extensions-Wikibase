<?php

namespace Wikibase\Repo\Tests\ParserOutput\PlaceholderExpander;

use Language;
use OutputPage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\LanguageFallbackChain;
use Wikibase\Repo\ParserOutput\PlaceholderExpander\ExternallyRenderedEntityViewPlaceholderExpander;
use Wikibase\Repo\ParserOutput\PlaceholderExpander\WbUiRequestInspector;
use Wikibase\Repo\ParserOutput\TermboxView;
use Wikibase\Repo\View\RepoSpecialPageLinker;
use Wikibase\View\EntityTermsView;
use Wikibase\View\Termbox\Renderer\TermboxRenderer;
use Wikibase\View\Termbox\Renderer\TermboxRenderingException;

/**
 * @covers \Wikibase\Repo\ParserOutput\PlaceholderExpander\ExternallyRenderedEntityViewPlaceholderExpander
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ExternallyRenderedEntityViewPlaceholderExpanderTest extends TestCase {

	use \PHPUnit4And6Compat;

	/** @var OutputPage|MockObject */
	private $outputPage;

	/** @var WbUiRequestInspector|MockObject */
	private $requestInspector;

	/** @var TermboxRenderer|MockObject */
	private $termboxRenderer;

	/** @var EntityId|MockObject */
	private $entityId;

	/** @var RepoSpecialPageLinker|MockObject */
	private $specialPageLinker;

	/** @var LanguageFallbackChain|MockObject */
	private $languageFallbackChain;

	protected function setUp() {
		parent::setUp();

		$this->outputPage = $this->createMock( OutputPage::class );
		$this->requestInspector = $this->createMock( WbUiRequestInspector::class );
		$this->termboxRenderer = $this->createMock( TermboxRenderer::class );
		$this->entityId = new ItemId( 'Q42' );
		$this->specialPageLinker = $this->createMock( RepoSpecialPageLinker::class );
		$this->languageFallbackChain = $this->createMock( LanguageFallbackChain::class );
	}

	public function testGivenWbUiPlaceholderAndDefaultRequest_getHtmlForPlaceholderReturnsInjectedMarkup() {
		$html = '<div>termbox</div>';

		$this->outputPage->expects( $this->once() )
			->method( 'getProperty' )
			->with( TermboxView::TERMBOX_MARKUP )
			->willReturn( $html );

		$this->requestInspector->expects( $this->once() )
			->method( 'isDefaultRequest' )
			->with( $this->outputPage )
			->willReturn( true );

		$this->assertSame(
			$html,
			$this->newPlaceholderExpander()->getHtmlForPlaceholder( TermboxView::TERMBOX_PLACEHOLDER )
		);
	}

	public function testGivenWbUiPlaceholderAndDefaultRequestAndNoHtml_getHtmlForPlaceholderReturnsFallbackHtml() {
		$this->outputPage->expects( $this->once() )
			->method( 'getProperty' )
			->with( TermboxView::TERMBOX_MARKUP )
			->willReturn( null );

		$this->requestInspector->expects( $this->once() )
			->method( 'isDefaultRequest' )
			->with( $this->outputPage )
			->willReturn( true );

		$this->assertSame(
			ExternallyRenderedEntityViewPlaceholderExpander::FALLBACK_HTML,
			$this->newPlaceholderExpander()->getHtmlForPlaceholder( TermboxView::TERMBOX_PLACEHOLDER )
		);
	}

	public function testGivenWbUiPlaceholderAndNonDefaultRequest_getHtmlForPlaceholderReturnsRerenderedTermbox() {
		$html = '<div>html coming from SSR service</div>';

		$language = 'en';
		$this->outputPage->expects( $this->once() )
			->method( 'getLanguage' )
			->willReturn( Language::factory( $language ) );

		$editPageLink = '/edit/Q42';
		$this->specialPageLinker->expects( $this->once() )
			->method( 'getLink' )
			->with( EntityTermsView::TERMS_EDIT_SPECIAL_PAGE, [ $this->entityId->getSerialization() ] )
			->willReturn( $editPageLink );

		$this->termboxRenderer->expects( $this->once() )
			->method( 'getContent' )
			->with(
				$this->entityId,
				$language,
				$editPageLink,
				$this->languageFallbackChain
			)
			->willReturn( $html );

		$this->requestInspector->expects( $this->once() )
			->method( 'isDefaultRequest' )
			->with( $this->outputPage )
			->willReturn( false );

		$this->assertSame(
			$html,
			$this->newPlaceholderExpander()->getHtmlForPlaceholder( TermboxView::TERMBOX_PLACEHOLDER )
		);
	}

	public function testGivenRerenderCausesException_getHtmlForPlaceholderReturnsFallbackHtml() {
		$this->termboxRenderer->expects( $this->once() )
			->method( 'getContent' )
			->willThrowException( new TermboxRenderingException( 'sad' ) );

		$this->outputPage->expects( $this->once() )
			->method( 'getLanguage' )
			->willReturn( Language::factory( 'de' ) );

		$this->requestInspector->expects( $this->once() )
			->method( 'isDefaultRequest' )
			->with( $this->outputPage )
			->willReturn( false );

		$this->assertSame(
			ExternallyRenderedEntityViewPlaceholderExpander::FALLBACK_HTML,
			$this->newPlaceholderExpander()->getHtmlForPlaceholder( TermboxView::TERMBOX_PLACEHOLDER )
		);
	}

	/**
	 * @expectedException \RuntimeException
	 */
	public function testGivenUnknownPlaceholder_getHtmlForPlaceholderThrows() {
		$this->newPlaceholderExpander()->getHtmlForPlaceholder( 'unknown-placeholder' );
	}

	private function newPlaceholderExpander() {
		return new ExternallyRenderedEntityViewPlaceholderExpander(
			$this->outputPage,
			$this->requestInspector,
			$this->termboxRenderer,
			$this->entityId,
			$this->specialPageLinker,
			$this->languageFallbackChain
		);
	}

}
