<?php

namespace Wikibase\Lib\Formatters;

use Html;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\EntityId\EntityIdLabelFormatter;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\Lib\LanguageFallbackIndicator;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\Store\EntityExistenceChecker;
use Wikibase\Lib\Store\EntityRedirectChecker;
use Wikibase\Lib\Store\EntityTitleTextLookup;
use Wikibase\Lib\Store\EntityUrlLookup;

/**
 * Formats entity IDs by generating an HTML link to the corresponding page title.
 *
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 * @author Thiemo Kreuz
 */
class LabelsProviderEntityIdHtmlLinkFormatter extends EntityIdLabelFormatter {

	/**
	 * @var EntityExistenceChecker
	 */
	protected $entityExistenceChecker;

	/**
	 * @var EntityTitleTextLookup
	 */
	protected $entityTitleTextLookup;

	/**
	 * @var EntityUrlLookup
	 */
	protected $entityUrlLookup;

	/**
	 * @var EntityRedirectChecker
	 */
	protected $entityRedirectChecker;

	/**
	 * @var LanguageFallbackIndicator
	 */
	private $languageFallbackIndicator;

	/**
	 * @var NonExistingEntityIdHtmlFormatter
	 */
	private $nonExistingEntityIdHtmlFormatter;

	public function __construct(
		LabelDescriptionLookup $labelDescriptionLookup,
		LanguageNameLookup $languageNameLookup,
		EntityExistenceChecker $entityExistenceChecker,
		EntityTitleTextLookup $entityTitleTextLookup,
		EntityUrlLookup $entityUrlLookup,
		EntityRedirectChecker $entityRedirectChecker
	) {
		parent::__construct( $labelDescriptionLookup );
		$this->languageFallbackIndicator = new LanguageFallbackIndicator( $languageNameLookup );
		$this->entityExistenceChecker = $entityExistenceChecker;
		$this->entityTitleTextLookup = $entityTitleTextLookup;
		$this->entityUrlLookup = $entityUrlLookup;
		$this->entityRedirectChecker = $entityRedirectChecker;
		$this->nonExistingEntityIdHtmlFormatter = new NonExistingEntityIdHtmlFormatterLinker(
			$this->entityTitleTextLookup,
			$this->entityUrlLookup
		);
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

		// We can skip the potentially expensive exists() check if we found a term.
		if ( $term === null && !$this->entityExistenceChecker->exists( $entityId ) ) {
			return $this->nonExistingEntityIdHtmlFormatter->formatEntityId( $entityId );
		}

		if ( $term === null ) {
			$label = $entityId->getSerialization();
		} else {
			$label = $term->getText();
		}

		$linkAttribs = $this->getAttributes( $entityId, $term );

		$html = Html::element( 'a', $linkAttribs, $label );

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
			'title' => $this->entityTitleTextLookup->getPrefixedText( $entityId ),
			'href' => $this->entityUrlLookup->getLinkUrl( $entityId ),
		];

		if ( $term instanceof TermFallback
			&& $term->getActualLanguageCode() !== $term->getLanguageCode()
		) {
			$attributes['lang'] = $term->getActualLanguageCode();
			// TODO: Mark as RTL/LTR if appropriate.
		}

		if ( $this->entityRedirectChecker->isRedirect( $entityId ) ) {
			$attributes['class'] = 'mw-redirect';
		}

		return $attributes;
	}

}
