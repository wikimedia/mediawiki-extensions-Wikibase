<?php

namespace Wikibase\Repo\ParserOutput;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\TermList;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\View\CacheableEntityTermsView;
use Wikibase\View\EntityTermsView;
use Wikibase\View\LocalizedTextProvider;
use Wikibase\View\SpecialPageLinker;
use Wikibase\View\Termbox\Renderer\TermboxRenderer;
use Wikibase\View\Termbox\Renderer\TermboxRenderingException;

/**
 * @license GPL-2.0-or-later
 */
class TermboxView implements CacheableEntityTermsView {

	/* public */ const TERMBOX_PLACEHOLDER = 'wb-ui';

	/* public */ const TERMBOX_MARKUP = 'termbox-markup';

	private $fallbackChainFactory;
	private $renderer;
	private $specialPageLinker;
	private $textInjector;

	/**
	 * @var LocalizedTextProvider
	 */
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
		return htmlspecialchars(
			$this->textProvider->get( 'parentheses', [ $entityId->getSerialization() ] )
		);
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
			// TODO Log
			return null;
		}
	}

}
