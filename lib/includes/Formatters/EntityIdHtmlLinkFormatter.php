<?php

namespace Wikibase\Lib;

use Html;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\EntityId\EntityIdLabelFormatter;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookupException;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\Lib\Store\EntityTitleLookup;

/**
 * Formats entity IDs by generating an HTML link to the corresponding page title.
 *
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 * @author Thiemo Mättig
 */
class EntityIdHtmlLinkFormatter extends EntityIdLabelFormatter {

	/**
	 * @todo remove once lookupEntityDescription has been pushed up to the base class
	 * @var LabelDescriptionLookup
	 */
	private $labelDescriptionLookup;

	/**
	 * @var LanguageFallbackIndicator
	 */
	private $languageFallbackIndicator;

	/**
	 * @var EntityTitleLookup
	 */
	protected $entityTitleLookup;

	public function __construct(
		LabelDescriptionLookup $labelDescriptionLookup,
		EntityTitleLookup $entityTitleLookup,
		LanguageNameLookup $languageNameLookup
	) {
		parent::__construct( $labelDescriptionLookup );

		$this->labelDescriptionLookup = $labelDescriptionLookup;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->languageFallbackIndicator = new LanguageFallbackIndicator( $languageNameLookup );
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
			return $this->getHtmlForNonExistent( $entityId );
		}

		$label = $this->lookupEntityLabel( $entityId );
		$description = $this->lookupEntityDescription( $entityId );

		if ( $description === null ) {
			$titleText = $entityId->getSerialization();
		} else {
			$titleText = wfMessage(
				'wikibase-entity-link-hover-text',
				$description->getText(),
				$entityId->getSerialization()
			)->text();
		}

		$url = $title->isLocal() ? $title->getLocalURL() : $title->getFullURL();

		if ( $label ) {
			return $this->getHtmlForTerm( $url, $label, $titleText );
		} elseif ( $title->isLocal() && !$title->exists() ) {
			return $this->getHtmlForNonExistent( $entityId );
		}

		$attributes = [
			'title' => $titleText,
			'href' => $url
		];

		$html = Html::element( 'a', $attributes, $entityId->getSerialization() );

		return $html;
	}

	/**
	 * @param string $targetUrl
	 * @param Term $term
	 * @param string $titleText
	 *
	 * @return string HTML
	 */
	private function getHtmlForTerm( $targetUrl, Term $term, $titleText = '' ) {
		$fallbackIndicatorHtml = '';

		$attributes = [
			'title' => $titleText,
			'href' => $targetUrl
		];

		if ( $term instanceof TermFallback ) {
			$fallbackIndicatorHtml = $this->languageFallbackIndicator->getHtml( $term );

			if ( $term->getActualLanguageCode() !== $term->getLanguageCode() ) {
				$attributes['lang'] = $term->getActualLanguageCode();
				//TODO: mark as rtl/ltr if appropriate.
			}
		}

		$html = Html::element( 'a', $attributes, $term->getText() );

		return $html . $fallbackIndicatorHtml;
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return string HTML
	 */
	private function getHtmlForNonExistent( EntityId $entityId ) {
		$attributes = [ 'class' => 'wb-entity-undefinedinfo' ];

		$message = wfMessage( 'parentheses',
			wfMessage( 'wikibase-deletedentity-' . $entityId->getEntityType() )->text()
		);

		$undefinedInfo = Html::element( 'span', $attributes, $message );

		$separator = wfMessage( 'word-separator' )->text();
		return $entityId->getSerialization() . $separator . $undefinedInfo;
	}

	/**
	 * Lookup a description for an entity
	 *
	 * @todo Push this up to EntityIdLabelFormattre
	 *
	 * @param EntityId $entityId
	 *
	 * @return Term|null Null if no label was found or the entity does not exist
	 */
	protected function lookupEntityDescription( EntityId $entityId ) {
		try {
			return $this->labelDescriptionLookup->getDescription( $entityId );
		} catch ( LabelDescriptionLookupException $e ) {
			return null;
		}
	}

}
