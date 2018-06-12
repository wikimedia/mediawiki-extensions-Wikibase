<?php

namespace Wikibase\Lib;

use Html;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\EntityId\EntityIdLabelFormatter;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup;

/**
 * Formats entity IDs by generating an HTML link to the corresponding page title.
 *
 * @license GPL-2.0-or-later
 */
class ItemIdHtmlLinkFormatter extends EntityIdLabelFormatter {

	/**
	 * @var EntityTitleLookup
	 */
	protected $entityTitleLookup;

	/**
	 * @var LanguageFallbackIndicator
	 */
	private $languageFallbackIndicator;

	/**
	 * @var NonExistingEntityIdHtmlFormatter
	 */
	private $nonExistingFormatter;

	public function __construct(
		LanguageFallbackLabelDescriptionLookup $labelDescriptionLookup,
		EntityTitleLookup $entityTitleLookup,
		LanguageNameLookup $languageNameLookup
	) {
		parent::__construct( $labelDescriptionLookup );

		$this->entityTitleLookup = $entityTitleLookup;
		$this->languageFallbackIndicator = new LanguageFallbackIndicator( $languageNameLookup );
		$this->nonExistingFormatter = new NonExistingEntityIdHtmlFormatter( 'wikibase-deletedentity-' );
	}

	/**
	 * @see EntityIdFormatter::formatEntityId
	 *
	 * @param EntityId $entityId
	 *
	 * @return string HTML
	 */
	public function formatEntityId( EntityId $entityId ) {
		$title = $this->entityTitleLookup->getTitleForId( $entityId );

		if ( $title === null ) {
			return $this->nonExistingFormatter->formatEntityId( $entityId );
		}

		/** @var TermFallback $term */
		$term = $this->lookupEntityLabel( $entityId );

		// We can skip the expensive exists() check if we found a term.
		if ( $term !== null ) {
			$label = $term->getText();
		} elseif ( $title->isLocal() && !$title->exists() ) {
			return $this->nonExistingFormatter->formatEntityId( $entityId );
		} else {
			$label = $entityId->getSerialization();
		}

		$html = Html::element( 'a', $this->getAttributes( $title, $term ), $label );

		//FIXME Remove this check once all the tests are migrated
		if ( $term instanceof TermFallback ) {
			$html .= $this->languageFallbackIndicator->getHtml( $term );
		}

		/*
		 *  * Exists?
		 *  * Is redirect?
		 *  * Short name
		 *  * Is rendered using fallback language?
		 *     * Which one?
		 *     * Is this a transliteration?
		 */

		return $html;
	}

	/**
	 * @param Title $title
	 * @param Term|null $term
	 *
	 * @return string[]
	 */
	private function getAttributes( Title $title, Term $term = null ) {
		$attributes = [
			'title' => $title->getPrefixedText(),
			'href' => $title->isLocal() ? $title->getLocalURL() : $title->getFullURL()
		];

		if ( $term instanceof TermFallback
			&& $term->getActualLanguageCode() !== $term->getLanguageCode()
		) {
			$attributes['lang'] = $term->getActualLanguageCode();
			// TODO: Mark as RTL/LTR if appropriate.
		}

		if ( $title->isLocal() && $title->isRedirect() ) {
			$attributes['class'] = 'mw-redirect';
		}

		return $attributes;
	}

}
