<?php

declare( strict_types = 1 );

namespace Wikibase\Repo;

use Html;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\Store\EntityTitleLookup;

/**
 * Class representing the disambiguation of a list of WikibaseItems.
 *
 * @see ItemDisambiguationFactory
 * @license GPL-2.0-or-later
 */
class ItemDisambiguation {

	private EntityTitleLookup $titleLookup;
	private LanguageNameLookup $languageNameLookup;

	public function __construct(
		EntityTitleLookup $titleLookup,
		LanguageNameLookup $languageNameLookup
	) {
		$this->titleLookup = $titleLookup;
		$this->languageNameLookup = $languageNameLookup;
	}

	/**
	 * Builds and returns the HTML to represent the WikibaseItem.
	 *
	 * @param TermSearchResult[] $searchResults
	 *
	 * @return string HTML
	 */
	public function getHTML( array $searchResults ): string {
		return '<ul class="wikibase-disambiguation">'
			. implode( '', array_map( [ $this, 'getResultHtml' ], $searchResults ) )
			. '</ul>';
	}

	/**
	 * @return string HTML
	 */
	public function getResultHtml( TermSearchResult $searchResult ): string {
		$idHtml = $this->getIdHtml( $searchResult->getEntityId() );

		$displayLabel = $searchResult->getDisplayLabel();
		$displayDescription = $searchResult->getDisplayDescription();
		$matchedTerm = $searchResult->getMatchedTerm();

		$labelHtml = $this->getLabelHtml(
			$displayLabel
		);

		$descriptionHtml = $this->getDescriptionHtml(
			$displayDescription
		);

		$matchHtml = $this->getMatchHtml(
			$matchedTerm, $displayLabel
		);

		$result = $idHtml;

		if ( $labelHtml !== '' || $descriptionHtml !== '' || $matchHtml !== '' ) {
			$result .= wfMessage( 'colon-separator' )->escaped();
		}

		if ( $labelHtml !== '' ) {
			$result .= $labelHtml;
		}

		if ( $labelHtml !== '' && $descriptionHtml !== '' ) {
			$result .= wfMessage( 'comma-separator' )->escaped();
		}

		if ( $descriptionHtml !== '' ) {
			$result .= $descriptionHtml;
		}

		if ( $matchHtml !== '' ) {
			$result .= $matchHtml;
		}

		$result = Html::rawElement( 'li', [ 'class' => 'wikibase-disambiguation' ], $result );
		return $result;
	}

	/**
	 * Returns HTML representing the label in the display language (or an appropriate fallback).
	 *
	 * @return string HTML
	 */
	private function getIdHtml( EntityId $entityId ): string {
		$title = $this->titleLookup->getTitleForId( $entityId );

		$idElement = Html::element(
			'a',
			[
				'title' => $title ? $title->getPrefixedText() : '',
				'href' => $title ? $title->getLocalURL() : '',
				'class' => 'wb-itemlink-id',
			],
			$entityId->getSerialization()
		);

		return $idElement;
	}

	/**
	 * Returns HTML representing the label in the display language (or an appropriate fallback).
	 *
	 * @return string HTML
	 */
	private function getLabelHtml( ?Term $label ): string {
		if ( !$label ) {
			return '';
		}

		//TODO: include actual language if $label is a FallbackTerm
		$labelElement = Html::element(
			'span',
			[ 'class' => 'wb-itemlink-label' ],
			$label->getText()
		);
		return $labelElement;
	}

	/**
	 * Returns HTML representing the description in the display language (or an appropriate fallback).
	 *
	 * @return string HTML
	 */
	private function getDescriptionHtml( ?Term $description ): string {
		if ( !$description ) {
			return '';
		}

		//TODO: include actual language if $description is a FallbackTerm
		$descriptionElement = Html::element(
			'span',
			[ 'class' => 'wb-itemlink-description' ],
			$description->getText()
		);
		return $descriptionElement;
	}

	/**
	 * Returns HTML representing the matched term in the search language (or an appropriate fallback).
	 * The matched text and language are wrapped using the wikibase-itemlink-userlang-wrapper message.
	 * If the matched term has the same text as the display label, an empty string is returned.
	 *
	 * @return string HTML
	 */
	private function getMatchHtml( Term $match, ?Term $label ): string {
		if ( $label && $label->getText() == $match->getText() ) {
			return '';
		}

		$text = $match->getText();
		$language = $this->languageNameLookup->getName( $match->getLanguageCode() );

		$matchElement = $descriptionElement = Html::element(
			'span',
			[ 'class' => 'wb-itemlink-match' ],
			wfMessage( 'wikibase-itemlink-userlang-wrapper', $language, $text )->text()
		);

		return $matchElement;
	}

}
