<?php

namespace Wikibase\Lib;

use Html;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\LabelLookup;

/**
 * Formats entity IDs by generating an HTML link to the corresponding page title.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Adrian Lang
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
	 * @param LabelLookup $labelLookup
	 * @param EntityTitleLookup $entityTitleLookup
	 * @param LanguageNameLookup $languageNameLookup
	 */
	public function __construct(
		LabelLookup $labelLookup,
		EntityTitleLookup $entityTitleLookup,
		LanguageNameLookup $languageNameLookup
	) {
		parent::__construct( $labelLookup );

		$this->entityTitleLookup = $entityTitleLookup;
		$this->languageNameLookup = $languageNameLookup;
	}

	/**
	 * @see EntityIdFormatter::formatEntityId
	 *
	 * @param EntityId $entityId
	 *
	 * @return string
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
	 * @param string $languageCode
	 * @param string $inLanguage
	 *
	 * @return string
	 */
	private function getLanguageName( $languageCode, $inLanguage ) {
		return $this->languageNameLookup->getName( $languageCode, $inLanguage );
	}

	private function getHtmlForFallbackIndicator( TermFallback $term ) {
		$requestedLanguage = $term->getLanguageCode();
		$actualLanguage = $term->getActualLanguageCode();
		$sourceLanguage = $term->getSourceLanguageCode();

		// FIXME: TermFallback should either return equal values or null
		$sourceLanguage = $sourceLanguage === null ? $actualLanguage : $sourceLanguage;

		$isInRequestedLanguage = $actualLanguage === $requestedLanguage;
		$isInSourceLanguage = $actualLanguage === $sourceLanguage;

		if ( $isInRequestedLanguage && $isInSourceLanguage ) {
			// This is neither a fallback nor a transliteration
			return '';
		}

		$sourceLanguageName = $this->getLanguageName( $sourceLanguage, $requestedLanguage );
		$actualLanguageName = $this->getLanguageName( $actualLanguage, $requestedLanguage );

		// Generate indicator text
		if ( $isInSourceLanguage ) {
			$text = $sourceLanguageName;
		} else {
			$text = wfMessage(
				'wikibase-language-fallback-transliteration-hint',
				$sourceLanguageName,
				$actualLanguageName
			)->text();
		}

		// Generate HTML class names
		$classes = 'wb-language-fallback-indicator';
		if ( !$isInSourceLanguage ) {
			$classes .= ' wb-language-fallback-transliteration';
		}
		if ( !$isInRequestedLanguage
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
