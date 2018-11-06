<?php

namespace Wikibase\Repo\ParserOutput;

use Exception;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\TermList;
use Wikibase\LanguageFallbackChain;
use Wikibase\View\CacheableEntityTermsView;
use Wikibase\View\Termbox\Renderer\TermboxRenderer;

/**
 * @license GPL-2.0-or-later
 */
class TermboxView implements CacheableEntityTermsView {

	// todo I suggest we use the desktop view as fallback
	/* public */ const FALLBACK_HTML = '<div class="wikibase-entitytermsview"></div>';

	private $fallbackChain;
	private $renderer;

	public function __construct(
		LanguageFallbackChain $fallbackChain,
		TermboxRenderer $renderer
	) {
		$this->fallbackChain = $fallbackChain;
		$this->renderer = $renderer;
	}

	public function getHtml(
		$mainLanguageCode,
		TermList $labels,
		TermList $descriptions,
		AliasGroupList $aliasGroups = null,
		EntityId $entityId = null
	) {
		try {
			return $this->renderer->getContent( $entityId, $mainLanguageCode );
		} catch ( Exception $exception ) {
			return self::FALLBACK_HTML;
		}
	}

	/**
	 * FIXME This actually sets the html within h1#firstHeading.
	 * The correct title could either be set here, or it gets hidden, similarly to how the Lemma title is handled
	 * for WikibaseLexeme.
	 */
	public function getTitleHtml( EntityId $entityId = null ) {
		return '';
	}

	/**
	 * @see \Wikibase\View\ViewPlaceHolderEmitter
	 */
	public function getPlaceholders(
		EntityDocument $entity,
		$languageCode
	) {
		return [];
	}

}
