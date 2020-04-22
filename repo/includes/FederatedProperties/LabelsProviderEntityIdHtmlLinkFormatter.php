<?php

namespace Wikibase\Repo\FederatedProperties;

use Html;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\EntityId\EntityIdLabelFormatter;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\Lib\LanguageFallbackIndicator;
use Wikibase\Lib\Store\EntityUrlLookup;

/**
 * Copy of LabelsProviderEntityIdHtmlLinkFormatter from Lib that has some elements removed to simplify
 * implementation for federated properties
 *
 * @license GPL-2.0-or-later
 */
class LabelsProviderEntityIdHtmlLinkFormatter extends EntityIdLabelFormatter {

	/**
	 * @var EntityUrlLookup
	 */
	private $entityUrlLookup;

	/**
	 * @var LanguageFallbackIndicator
	 */
	private $languageFallbackIndicator;

	public function __construct(
		LabelDescriptionLookup $labelDescriptionLookup,
		EntityUrlLookup $entityUrlLookup,
		LanguageFallbackIndicator $languageFallbackIndicator
	) {
		parent::__construct( $labelDescriptionLookup );

		$this->entityUrlLookup = $entityUrlLookup;
		$this->languageFallbackIndicator = $languageFallbackIndicator;
	}

	/**
	 * @see EntityIdFormatter::formatEntityId
	 *
	 * @param EntityId $entityId
	 *
	 * @return string HTML
	 */
	public function formatEntityId( EntityId $entityId ) {
		$term = $this->lookupEntityLabel( $entityId );

		// We can skip the potentially expensive isKnown() check if we found a term.
		if ( $term !== null ) {
			$label = $term->getText();
		} else {
			$label = $entityId->getSerialization();
		}

		$html = Html::element( 'a', $this->getAttributes( $entityId, $term ), $label );

		if ( $term instanceof TermFallback ) {
			$html .= $this->languageFallbackIndicator->getHtml( $term );
		}

		return $html;
	}

	/**
	 * @param EntityId $entityId
	 * @param Term|null $term
	 *
	 * @return string[]
	 */
	private function getAttributes( EntityId $entityId, Term $term = null ) {
		$attributes = [
			'href' => $this->entityUrlLookup->getFullUrl( $entityId )
		];

		if ( $term instanceof TermFallback
			&& $term->getActualLanguageCode() !== $term->getLanguageCode()
		) {
			$attributes['lang'] = $term->getActualLanguageCode();
			// TODO: Mark as RTL/LTR if appropriate.
		}

		return $attributes;
	}

}
