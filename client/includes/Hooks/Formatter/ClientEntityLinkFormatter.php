<?php

namespace Wikibase\Client\Hooks\Formatter;

use HtmlArmor;
use MediaWiki\Language\Language;
use MediaWiki\Languages\LanguageFactory;
use Wikibase\DataModel\Entity\EntityId;

/**
 * @license GPL-2.0-or-later
 */
class ClientEntityLinkFormatter {
	/**
	 * @var LanguageFactory
	 */
	private LanguageFactory $languageFactory;

	/**
	 * @param LanguageFactory $languageFactory
	 */
	public function __construct( LanguageFactory $languageFactory ) {
		$this->languageFactory = $languageFactory;
	}

	/**
	 * Returns a formatted HTML string containing the localized entity label and id.
	 * @param EntityId $entityId
	 * @param Language $pageLanguage
	 * @param array|null $labelData array containing the label's text and language
	 * @return string
	 */
	public function getHtml( EntityId $entityId, Language $pageLanguage, ?array $labelData = null ): string {
		[ $labelText, $labelLang ] = $this->extractTextAndLanguage( $labelData, $pageLanguage );

		$idHtml = '<span class="wb-itemlink-id">'
			. wfMessage(
				'wikibase-itemlink-id-wrapper',
				$entityId->getSerialization()
			)->inContentLanguage()->escaped()
			. '</span>';

		$labelHtml = '<span class="wb-itemlink-label"'
			. ' lang="' . htmlspecialchars( $labelLang->getCode() ) . '"'
			. ' dir="' . htmlspecialchars( $labelLang->getDir() ) . '">'
			. HtmlArmor::getHtml( $labelText )
			. '</span>';
		return '<span class="wb-itemlink">'
			. wfMessage( 'wikibase-itemlink' )->rawParams(
				$labelHtml,
				$idHtml
			)->inContentLanguage()->escaped()
			. '</span>';
	}

	/**
	 * @param string[]|null $termData A term record as returned by
	 * TermLanguageFallbackChain::extractPreferredValueOrAny(),
	 * containing the 'value' and 'language' fields, or null
	 * or an empty array.
	 * @return array{0: string, 1: Language}
	 * @see TermLanguageFallbackChain::extractPreferredValueOrAny
	 *
	 */
	private function extractTextAndLanguage( ?array $termData, Language $pageLanguage ): array {
		if ( $termData ) {
			return [
				$termData['value'],
				$this->languageFactory->getLanguage( $termData['language'] ),
			];
		} else {
			return [
				'',
				$pageLanguage,
			];
		}
	}

	/**
	 * Creates a formatted string containing the localized entity label and description
	 * to be displayed on link hover.
	 * @param Language $pageLanguage
	 * @param array|null $labelData array containing the label's text and language
	 * @param array|null $descriptionData array containing the description's text and language
	 * @return string|null
	 */
	public function getTitleAttribute(
		Language $pageLanguage,
		?array $labelData = null,
		?array $descriptionData = null
	): ?string {
		[ $labelText, $labelLang ] = $this->extractTextAndLanguage( $labelData, $pageLanguage );
		[ $descriptionText, $descriptionLang ] = $this->extractTextAndLanguage( $descriptionData, $pageLanguage );
		$titleText = ( $labelText !== '' )
			? $labelLang->getDirMark() . $labelText
			. $pageLanguage->getDirMark()
			: null;

		if ( $descriptionText !== '' ) {
			$descriptionText = $descriptionLang->getDirMark() . $descriptionText
				. $pageLanguage->getDirMark();
			return wfMessage(
				'wikibase-itemlink-title',
				$titleText,
				$descriptionText
			)->inContentLanguage()->text();
		} else {
			return $titleText;
		}
	}

}
