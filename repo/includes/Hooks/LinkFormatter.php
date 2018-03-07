<?php
/**
 * Created by PhpStorm.
 * User: smalyshev
 * Date: 3/7/18
 * Time: 11:01 AM
 */

namespace Wikibase\Repo\Hooks;

use HtmlArmor;
use Language;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Store\EntityIdLookup;

/**
 * Utility class to format Wikidata links for usage in hooks.
 */
class LinkFormatter {
	/**
	 * @var Language
	 */
	private $pageLanguage;
	/**
	 * @var EntityIdLookup
	 */
	private $entityIdLookup;

	public function __construct(
		EntityIdLookup $entityIdLookup,
		Language $pageLanguage
	) {
		$this->entityIdLookup = $entityIdLookup;
		$this->pageLanguage = $pageLanguage;
	}

	/**
	 * Produce link HTML from title and label data.
	 * @param Title $title
	 * @param array|null $labelData
	 * @return null|string
	 */
	public function getHtmlForTitle( Title $title, array $labelData = null ) {
		$entityId = $this->lookupLocalId( $title );
		if ( $entityId === null ) {
			return null;
		}
		return $this->getHtml( $entityId->getSerialization(), $labelData );
	}

	/**
	 * Lookup entity ID of a local title.
	 * @param Title $title
	 * @return null|EntityId
	 */
	private function lookupLocalId( Title $title ) {
		return $this->entityIdLookup->getEntityIdForTitle( $title );
	}

	/**
	 * Produce link HTML from serialized Entity ID and label data.
	 * @param string $entityIdSerialization
	 * @param string[]|null $labelData
	 *
	 * @return string
	 */
	public function getHtml( $entityIdSerialization, array $labelData = null ) {
		/** @var Language $labelLang */
		list( $labelText, $labelLang ) = $this->extractTextAndLanguage( $labelData );

		$idHtml = '<span class="wb-itemlink-id">'
				  . wfMessage(
					  'wikibase-itemlink-id-wrapper',
					  $entityIdSerialization
				  )->inContentLanguage()->escaped()
				  . '</span>';

		$labelHtml = '<span class="wb-itemlink-label"'
					 . ' lang="' . htmlspecialchars( $labelLang->getHtmlCode() ) . '"'
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
	 * LanguageFallbackChain::extractPreferredValueOrAny(),
	 * containing the 'value' and 'language' fields, or null
	 * or an empty array.
	 *
	 * @see LanguageFallbackChain::extractPreferredValueOrAny
	 *
	 * @return array list( string $text, Language $language )
	 */
	private function extractTextAndLanguage( array $termData = null ) {
		if ( $termData ) {
			return [
				$termData['value'],
				Language::factory( $termData['language'] )
			];
		} else {
			return [
				'',
				$this->pageLanguage
			];
		}
	}

	/**
	 * Get "title" attribute for Wikidata entity link.
	 * @param Title $title
	 * @param string[]|null $labelData
	 * @param string[]|null $descriptionData
	 *
	 * @return string The plain, unescaped title="…" attribute for the link.
	 */
	public function getTitleAttribute( Title $title, array $labelData = null, array $descriptionData = null ) {
		/** @var Language $labelLang */
		/** @var Language $descriptionLang */

		list( $labelText, $labelLang ) = $this->extractTextAndLanguage( $labelData );
		list( $descriptionText, $descriptionLang ) = $this->extractTextAndLanguage( $descriptionData );

		// Set title attribute for constructed link, and make tricks with the directionality to get it right
		$titleText = ( $labelText !== '' )
			? $labelLang->getDirMark() . $labelText
			  . $this->pageLanguage->getDirMark()
			: $title->getPrefixedText();

		if ( $descriptionText !== '' ) {
			$descriptionText = $descriptionLang->getDirMark() . $descriptionText
							   . $this->pageLanguage->getDirMark();
			return wfMessage(
				'wikibase-itemlink-title',
				$titleText,
				$descriptionText
			)->inContentLanguage()->text();
		} else {
			return $titleText; // no description, just display the title then
		}
	}
}
