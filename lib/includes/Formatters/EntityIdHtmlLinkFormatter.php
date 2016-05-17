<?php

namespace Wikibase\Lib;

use Html;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\EntityId\EntityIdLabelFormatter;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\Lib\Store\EntityTitleLookup;

/**
 * Formats entity IDs by generating an HTML link to the corresponding page title.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 * @author Thiemo Mättig
 */
class EntityIdHtmlLinkFormatter extends EntityIdLabelFormatter {

	/**
	 * @var LanguageNameLookup
	 */
	private $languageNameLookup;

	/**
	 * @var EntityTitleLookup
	 */
	protected $entityTitleLookup;

	/**
	 * @param LabelDescriptionLookup $labelDescriptionLookup
	 * @param EntityTitleLookup $entityTitleLookup
	 * @param LanguageNameLookup $languageNameLookup
	 */
	public function __construct(
		LabelDescriptionLookup $labelDescriptionLookup,
		EntityTitleLookup $entityTitleLookup,
		LanguageNameLookup $languageNameLookup
	) {
		parent::__construct( $labelDescriptionLookup );

		$this->entityTitleLookup = $entityTitleLookup;
		$this->languageNameLookup = $languageNameLookup;
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

		$term = $this->lookupEntityLabel( $entityId );

		if ( $term ) {
			return $this->getHtmlForTerm( $title->getLocalURL(), $term, $title->getPrefixedText() );
		} elseif ( !$title->exists() ) {
			return $this->getHtmlForNonExistent( $entityId );
		}

		$attributes = array(
			'title' => $title->getPrefixedText(),
			'href' => $title->getLocalURL()
		);

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

		$attributes = array(
			'title' => $titleText,
			'href' => $targetUrl
		);

		if ( $term instanceof TermFallback ) {
			$fallbackIndicatorHtml = $this->getHtmlForFallbackIndicator( $term );

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
		$attributes = array( 'class' => 'wb-entity-undefinedinfo' );

		$message = wfMessage( 'parentheses',
			wfMessage( 'wikibase-deletedentity-' . $entityId->getEntityType() )->text()
		);

		$undefinedInfo = Html::element( 'span', $attributes, $message );

		$separator = wfMessage( 'word-separator' )->text();
		return $entityId->getSerialization() . $separator . $undefinedInfo;
	}

	/**
	 * @param TermFallback $term
	 *
	 * @return string HTML
	 */
	private function getHtmlForFallbackIndicator( TermFallback $term ) {
		$requestedLanguage = $term->getLanguageCode();
		$actualLanguage = $term->getActualLanguageCode();
		$sourceLanguage = $term->getSourceLanguageCode();

		$isFallback = $actualLanguage !== $requestedLanguage;
		$isTransliterated = $sourceLanguage === null || $sourceLanguage !== $actualLanguage;

		if ( !$isFallback && !$isTransliterated ) {
			return '';
		}

		$text = $this->languageNameLookup->getName( $actualLanguage );

		if ( $isTransliterated ) {
			$text = wfMessage(
				'wikibase-language-fallback-transliteration-hint',
				$this->languageNameLookup->getName( $sourceLanguage ),
				$text
			)->text();
		}

		$classes = 'wb-language-fallback-indicator';
		if ( $isTransliterated ) {
			$classes .= ' wb-language-fallback-transliteration';
		}
		if ( $isFallback
			&& $this->getBaseLanguage( $actualLanguage ) === $this->getBaseLanguage( $requestedLanguage )
		) {
			$classes .= ' wb-language-fallback-variant';
		}

		$attributes = array( 'class' => $classes );

		$html = Html::element( 'sup', $attributes, $text );
		return $html;
	}

	/**
	 * @param string $languageCode
	 *
	 * @return string
	 */
	private function getBaseLanguage( $languageCode ) {
		return preg_replace( '/-.*/', '', $languageCode );
	}

}
