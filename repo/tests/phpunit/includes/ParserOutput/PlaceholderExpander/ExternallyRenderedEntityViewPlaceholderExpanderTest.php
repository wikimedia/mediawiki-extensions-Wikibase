<?php

namespace Wikibase\Repo\Tests\ParserOutput\PlaceholderExpander;

use MediaWiki\MediaWikiServices;
use OutputPage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\TermLanguageFallbackChain;
use Wikibase\Repo\Hooks\Helpers\OutputPageRevisionIdReader;
use Wikibase\Repo\Hooks\OutputPageEntityIdReader;
use Wikibase\Repo\ParserOutput\PlaceholderExpander\ExternallyRenderedEntityViewPlaceholderExpander;
use Wikibase\Repo\ParserOutput\PlaceholderExpander\TermboxRequestInspector;
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

	/** @var OutputPage|MockObject */
	private $outputPage;

	/** @var TermboxRequestInspector|MockObject */
	private $requestInspector;

	/** @var TermboxRenderer|MockObject */
	private $termboxRenderer;

	/** @var OutputPageEntityIdReader|MockObject */
	private $entityIdReader;

	/** @var RepoSpecialPageLinker|MockObject */
	private $specialPageLinker;

	/** @var LanguageFallbackChainFactory|MockObject */
	private $languageFallbackChainFactory;

	/** @var OutputPageRevisionIdReader|MockObject */
	private $revisionIdReader;

	private $enableUserSpecificSSR;

	protected function setUp(): void {
		parent::setUp();

		$this->outputPage = $this->createMock( OutputPage::class );
		$this->requestInspector = $this->createMock( TermboxRequestInspector::class );
		$this->termboxRenderer = $this->createMock( TermboxRenderer::class );
		$this->entityIdReader = $this->newEntityIdReaderReturningEntityId( new ItemId( 'Q42' ) );
		$this->specialPageLinker = $this->createMock( RepoSpecialPageLinker::class );
		$this->languageFallbackChainFactory = $this->newLanguageFallbackChainFactory();
		$this->revisionIdReader = $this->newOutputPageRevisionIdReader();
		$this->enableUserSpecificSSR = true;
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
		$revision = 4711;
		$this->outputPage->expects( $this->once() )
			->method( 'getLanguage' )
			->willReturn( MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( $language ) );

		$entityId = new ItemId( 'Q123' );
		$this->entityIdReader = $this->newEntityIdReaderReturningEntityId( $entityId );

		$editPageLink = '/edit/Q42';
		$this->specialPageLinker->expects( $this->once() )
			->method( 'getLink' )
			->with( EntityTermsView::TERMS_EDIT_SPECIAL_PAGE, [ $entityId->getSerialization() ] )
			->willReturn( $editPageLink );

		$languageFallbackChain = $this->createMock( TermLanguageFallbackChain::class );
		$this->languageFallbackChainFactory->expects( $this->once() )
			->method( 'newFromContext' )
			->with( $this->outputPage )
			->willReturn( $languageFallbackChain );

		$this->revisionIdReader = $this->newOutputPageRevisionIdReader( $revision );

		$this->termboxRenderer->expects( $this->once() )
			->method( 'getContent' )
			->with(
				$entityId,
				$revision,
				$language,
				$editPageLink,
				$languageFallbackChain
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

	public function testGivenRevisionIdReaderReturnsNull_getHtmlForPlaceholderReturnsFallbackHtml() {
		$this->revisionIdReader = $this->newOutputPageRevisionIdReader( EntityRevision::UNSAVED_REVISION );

		$this->termboxRenderer->expects( $this->never() )
			->method( 'getContent' );

		$this->assertSame(
			ExternallyRenderedEntityViewPlaceholderExpander::FALLBACK_HTML,
			$this->newPlaceholderExpander()->getHtmlForPlaceholder( TermboxView::TERMBOX_PLACEHOLDER )
		);
	}

	public function testGivenRerenderCausesException_getHtmlForPlaceholderReturnsFallbackHtml() {
		$this->termboxRenderer->expects( $this->once() )
			->method( 'getContent' )
			->willThrowException( new TermboxRenderingException( 'sad' ) );

		$this->outputPage->expects( $this->once() )
			->method( 'getLanguage' )
			->willReturn( MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'de' ) );

		$this->requestInspector->expects( $this->once() )
			->method( 'isDefaultRequest' )
			->with( $this->outputPage )
			->willReturn( false );

		$this->revisionIdReader = $this->newOutputPageRevisionIdReader( 4711 );

		$this->assertSame(
			ExternallyRenderedEntityViewPlaceholderExpander::FALLBACK_HTML,
			$this->newPlaceholderExpander()->getHtmlForPlaceholder( TermboxView::TERMBOX_PLACEHOLDER )
		);
	}

	public function testGivenUnknownPlaceholder_getHtmlForPlaceholderThrows() {
		$this->expectException( RuntimeException::class );
		$this->newPlaceholderExpander()->getHtmlForPlaceholder( 'unknown-placeholder' );
	}

	public function testGivenNonDefaultRequestAndDisabledUserSpecificSSR_getHtmlForPlaceholderReturnsDefaultMarkup() {
		$html = '<div>termbox</div>';

		$this->outputPage->expects( $this->once() )
			->method( 'getProperty' )
			->with( TermboxView::TERMBOX_MARKUP )
			->willReturn( $html );

		$this->requestInspector->expects( $this->once() )
			->method( 'isDefaultRequest' )
			->with( $this->outputPage )
			->willReturn( false );

		$this->enableUserSpecificSSR = false;
		$this->assertSame(
			$html,
			$this->newPlaceholderExpander()->getHtmlForPlaceholder( TermboxView::TERMBOX_PLACEHOLDER )
		);
	}

	private function newPlaceholderExpander() {
		return new ExternallyRenderedEntityViewPlaceholderExpander(
			$this->outputPage,
			$this->requestInspector,
			$this->termboxRenderer,
			$this->entityIdReader,
			$this->specialPageLinker,
			$this->languageFallbackChainFactory,
			$this->revisionIdReader,
			$this->enableUserSpecificSSR
		);
	}

	/**
	 * @return MockObject|OutputPageEntityIdReader
	 */
	protected function newEntityIdReaderReturningEntityId( $id ) {
		$entityIdReader = $this->createMock( OutputPageEntityIdReader::class );
		$entityIdReader->method( 'getEntityIdFromOutputPage' )
			->willReturn( $id );

		return $entityIdReader;
	}

	/**
	 * @return MockObject|LanguageFallbackChainFactory
	 */
	protected function newLanguageFallbackChainFactory() {
		$factory = $this->createMock( LanguageFallbackChainFactory::class );
		$factory->method( 'newFromContext' )
			->willReturn( $this->createMock( TermLanguageFallbackChain::class ) );

		return $factory;
	}

	/**
	 * @return MockObject|OutputPageRevisionIdReader
	 */
	protected function newOutputPageRevisionIdReader( $revisionId = EntityRevision::UNSAVED_REVISION ) {
		$reader = $this->createMock( OutputPageRevisionIdReader::class );
		$reader
			->method( 'getRevisionFromOutputPage' )
			->willReturn( $revisionId );

		return $reader;
	}

}
