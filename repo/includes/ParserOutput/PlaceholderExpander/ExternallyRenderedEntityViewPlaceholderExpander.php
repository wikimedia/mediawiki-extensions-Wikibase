<?php

namespace Wikibase\Repo\ParserOutput\PlaceholderExpander;

use OutputPage;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\LanguageFallbackChain;
use Wikibase\Repo\ParserOutput\TermboxView;
use Wikibase\Repo\View\RepoSpecialPageLinker;
use Wikibase\View\EntityTermsView;
use Wikibase\View\Termbox\Renderer\TermboxRenderer;
use Wikibase\View\Termbox\Renderer\TermboxRenderingException;

/**
 * @license GPL-2.0-or-later
 */
class ExternallyRenderedEntityViewPlaceholderExpander implements PlaceholderExpander {

	// render the root element and give client side re-rendering a chance
	/* public */ const FALLBACK_HTML = '<div class="wikibase-entitytermsview renderer-fallback"></div>';

	private $outputPage;

	private $requestInspector;

	private $termboxRenderer;

	private $entityId;

	private $specialPageLinker;

	private $languageFallbackChain;

	public function __construct(
		OutputPage $outputPage,
		WbUiRequestInspector $requestInspector,
		TermboxRenderer $termboxRenderer,
		EntityId $entityId,
		RepoSpecialPageLinker $specialPageLinker,
		LanguageFallbackChain $languageFallbackChain
	) {
		$this->outputPage = $outputPage;
		$this->requestInspector = $requestInspector;
		$this->termboxRenderer = $termboxRenderer;
		$this->entityId = $entityId;
		$this->specialPageLinker = $specialPageLinker;
		$this->languageFallbackChain = $languageFallbackChain;
	}

	public function getHtmlForPlaceholder( $name ) {
		if ( $name === TermboxView::TERMBOX_PLACEHOLDER ) {
			return $this->getHtml() ?: self::FALLBACK_HTML;
		}

		throw new \RuntimeException( "Unknown placeholder: $name" );
	}

	private function getHtml() {
		return $this->requestInspector->isDefaultRequest( $this->outputPage )
			? $this->outputPage->getProperty( TermboxView::TERMBOX_MARKUP )
			: $this->rerenderTermbox();
	}

	/**
	 * @return string|null
	 */
	private function rerenderTermbox() {
		try {
			return $this->termboxRenderer->getContent(
				$this->entityId,
				$this->outputPage->getLanguage()->getCode(),
				$this->specialPageLinker->getLink(
					EntityTermsView::TERMS_EDIT_SPECIAL_PAGE,
					[ $this->entityId->getSerialization() ]
				),
				$this->languageFallbackChain
			);
		} catch ( TermboxRenderingException $e ) {
			// TODO log
			return null;
		}
	}

}
