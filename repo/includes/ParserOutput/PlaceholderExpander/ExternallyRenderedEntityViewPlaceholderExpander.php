<?php

namespace Wikibase\Repo\ParserOutput\PlaceholderExpander;

use OutputPage;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Repo\Hooks\Helpers\OutputPageRevisionIdReader;
use Wikibase\Repo\Hooks\OutputPageEntityIdReader;
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

	private $entityIdReader;

	private $specialPageLinker;

	private $languageFallbackChainFactory;

	private $revisionIdReader;

	private $termboxUserSpecificSSR;

	public function __construct(
		OutputPage $outputPage,
		TermboxRequestInspector $requestInspector,
		TermboxRenderer $termboxRenderer,
		OutputPageEntityIdReader $entityIdReader,
		RepoSpecialPageLinker $specialPageLinker,
		LanguageFallbackChainFactory $languageFallbackChainFactory,
		OutputPageRevisionIdReader $revisionIdReader,
		$termboxUserSpecificSSR
	) {
		$this->outputPage = $outputPage;
		$this->requestInspector = $requestInspector;
		$this->termboxRenderer = $termboxRenderer;
		$this->entityIdReader = $entityIdReader;
		$this->specialPageLinker = $specialPageLinker;
		$this->languageFallbackChainFactory = $languageFallbackChainFactory;
		$this->revisionIdReader = $revisionIdReader;
		$this->termboxUserSpecificSSR = $termboxUserSpecificSSR;
	}

	public function getHtmlForPlaceholder( $name ) {
		if ( $name !== TermboxView::TERMBOX_PLACEHOLDER ) {
			throw new \RuntimeException( "Unknown placeholder: $name" );
		}

		return $this->getHtml() ?: self::FALLBACK_HTML;
	}

	private function getHtml() {
		return ( $this->requestInspector->isDefaultRequest( $this->outputPage )
			|| !$this->termboxUserSpecificSSR )
			? $this->outputPage->getProperty( TermboxView::TERMBOX_MARKUP )
			: $this->rerenderTermbox();
	}

	/**
	 * @return string|null
	 */
	private function rerenderTermbox() {
		$revision = $this->revisionIdReader->getRevisionFromOutputPage( $this->outputPage );

		if ( $revision === EntityRevision::UNSAVED_REVISION ) {
			return null;
		}

		try {
			$entityId = $this->entityIdReader->getEntityIdFromOutputPage( $this->outputPage );
			return $this->termboxRenderer->getContent(
				$entityId,
				$revision,
				$this->outputPage->getLanguage()->getCode(),
				$this->specialPageLinker->getLink(
					EntityTermsView::TERMS_EDIT_SPECIAL_PAGE,
					[ $entityId->getSerialization() ]
				),
				$this->languageFallbackChainFactory->newFromContext( $this->outputPage )
			);
		} catch ( TermboxRenderingException $e ) {
			// TODO log
			return null;
		}
	}

}
