<?php

namespace Wikibase\Repo\ParserOutput;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\View\CacheableEntityTermsView;
use Wikibase\View\EntityTermsView;
use Wikibase\View\LocalizedTextProvider;
use Wikibase\View\SpecialPageLinker;
use Wikibase\View\Termbox\Renderer\TermboxRenderer;
use Wikibase\View\Termbox\Renderer\TermboxRenderingException;
use Wikibase\View\ViewPlaceHolderEmitter;

/**
 * @license GPL-2.0-or-later
 */
class TermboxView implements CacheableEntityTermsView {

	public const TERMBOX_PLACEHOLDER = 'wb-ui';

	public const TERMBOX_MARKUP = 'termbox-markup';

	public const TERMBOX_VERSION = 2;

	public const CACHE_VERSION = 2;

	/** @var LanguageFallbackChainFactory */
	private $fallbackChainFactory;
	/** @var TermboxRenderer */
	private $renderer;
	/** @var SpecialPageLinker */
	private $specialPageLinker;
	/** @var TextInjector */
	private $textInjector;
	/** @var LocalizedTextProvider */
	private $textProvider;

	public function __construct(
		LanguageFallbackChainFactory $fallbackChainFactory,
		TermboxRenderer $renderer,
		LocalizedTextProvider $textProvider,
		SpecialPageLinker $specialPageLinker,
		TextInjector $textInjector
	) {
		$this->fallbackChainFactory = $fallbackChainFactory;
		$this->renderer = $renderer;
		$this->textProvider = $textProvider;
		$this->specialPageLinker = $specialPageLinker;
		$this->textInjector = $textInjector;
	}

	public function getHtml(
		$mainLanguageCode,
		TermList $labels,
		TermList $descriptions,
		AliasGroupList $aliasGroups = null,
		EntityId $entityId = null
	) {
		return $this->textInjector->newMarker( self::TERMBOX_PLACEHOLDER );
	}

	public function getTitleHtml( EntityId $entityId = null ) {
		return $this->textProvider->getEscaped( 'parentheses', [ $entityId->getSerialization() ] );
	}

	/**
	 * @see \Wikibase\View\ViewPlaceHolderEmitter
	 */
	public function getPlaceholders(
		EntityDocument $entity,
		$revision,
		$languageCode
	) {
		return [
			'wikibase-view-chunks' => $this->textInjector->getMarkers(),
			self::TERMBOX_MARKUP => $this->renderTermbox(
				$entity->getId(),
				$revision,
				$languageCode
			),
		];
	}

	/**
	 * @param EntityId $entityId
	 * @param int $revision
	 * @param string $mainLanguageCode
	 *
	 * @return string|null
	 */
	private function renderTermbox( EntityId $entityId, $revision, $mainLanguageCode ) {
		if ( $revision === EntityRevision::UNSAVED_REVISION ) {
			return ViewPlaceHolderEmitter::ERRONEOUS_PLACEHOLDER_VALUE;
		}

		try {
			return $this->renderer->getContent(
				$entityId,
				$revision,
				$mainLanguageCode,
				$this->specialPageLinker->getLink(
					EntityTermsView::TERMS_EDIT_SPECIAL_PAGE,
					[ $entityId->getSerialization() ]
				),
				$this->fallbackChainFactory->newFromLanguageCode( $mainLanguageCode )
			);
		} catch ( TermboxRenderingException $exception ) {
			return ViewPlaceHolderEmitter::ERRONEOUS_PLACEHOLDER_VALUE;
		}
	}

}
