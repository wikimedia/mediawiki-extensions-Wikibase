<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\ParserOutput\PlaceholderExpander;

use OutputPage;
use Wikibase\Lib\LanguageFallbackChainFactory;
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
	public const FALLBACK_HTML = '<div class="wikibase-entitytermsview renderer-fallback"></div>';

	/** @var OutputPage */
	private $outputPage;

	/** @var TermboxRequestInspector */
	private $requestInspector;

	/** @var TermboxRenderer */
	private $termboxRenderer;

	/** @var OutputPageEntityIdReader */
	private $entityIdReader;

	/** @var RepoSpecialPageLinker */
	private $specialPageLinker;

	/** @var LanguageFallbackChainFactory */
	private $languageFallbackChainFactory;

	/** @var OutputPageRevisionIdReader */
	private $revisionIdReader;

	/** @var bool */
	private $enableUserSpecificSSR;

	public function __construct(
		OutputPage $outputPage,
		TermboxRequestInspector $requestInspector,
		TermboxRenderer $termboxRenderer,
		OutputPageEntityIdReader $entityIdReader,
		RepoSpecialPageLinker $specialPageLinker,
		LanguageFallbackChainFactory $languageFallbackChainFactory,
		OutputPageRevisionIdReader $revisionIdReader,
		bool $enableUserSpecificSSR
	) {
		$this->outputPage = $outputPage;
		$this->requestInspector = $requestInspector;
		$this->termboxRenderer = $termboxRenderer;
		$this->entityIdReader = $entityIdReader;
		$this->specialPageLinker = $specialPageLinker;
		$this->languageFallbackChainFactory = $languageFallbackChainFactory;
		$this->revisionIdReader = $revisionIdReader;
		$this->enableUserSpecificSSR = $enableUserSpecificSSR;
	}

	public function getHtmlForPlaceholder( $name ): string {
		if ( $name !== TermboxView::TERMBOX_PLACEHOLDER ) {
			throw new \RuntimeException( "Unknown placeholder: $name" );
		}

		return $this->getHtml() ?: self::FALLBACK_HTML;
	}

	private function getHtml(): ?string {
		return ( $this->requestInspector->isDefaultRequest( $this->outputPage )
			|| !$this->enableUserSpecificSSR )
			? $this->outputPage->getProperty( TermboxView::TERMBOX_MARKUP )
			: $this->rerenderTermbox();
	}

	private function rerenderTermbox(): ?string {
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
			return null;
		}
	}

}
