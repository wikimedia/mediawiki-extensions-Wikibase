<?php

namespace Wikibase;

use Html;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\EntityIdFormatter;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Repo\Interactors\TermSearchResult;

/**
 * Class representing the disambiguation of a list of WikibaseItems.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author jeblad
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @authro Adam Shorland
 */
class ItemDisambiguation {

	/**
	 * @var EntityIdFormatter
	 */
	private $linkFormatter;

	/**
	 * @var LanguageNameLookup
	 */
	private $languageNameLookup;

	/**
	 * @var string
	 */
	private $displayLanguageCode;

	/**
	 * @since 0.5
	 *
	 * @param EntityIdFormatter $linkFormatter A formatter for generating HTML links for a given EntityId.
	 * @param LanguageNameLookup $languageNameLookup
	 * @param string $displayLanguageCode
	 */
	public function __construct(
		EntityIdFormatter $linkFormatter,
		LanguageNameLookup $languageNameLookup,
		$displayLanguageCode
	) {
		$this->linkFormatter = $linkFormatter;
		$this->languageNameLookup = $languageNameLookup;
		$this->displayLanguageCode = $displayLanguageCode;
	}

	/**
	 * Builds and returns the HTML to represent the WikibaseItem.
	 *
	 * @since 0.5
	 *
	 * @param TermSearchResult[] $searchResults
	 *
	 * @return string HTML
	 */
	public function getHTML( array $searchResults ) {
		return
			'<ul class="wikibase-disambiguation">' .
				implode( '', array_map(
					array( $this, 'getResultHtml' ),
					$searchResults
				) ).
			'</ul>';
	}

	/**
	 * @param TermSearchResult $searchResult
	 *
	 * @return string HTML
	 */
	public function getResultHtml( $searchResult ) {
		$idHtml = $this->linkFormatter->formatEntityId( $searchResult['entityId'] );

		$displayLabel = isset( $searchResult[TermSearchInteractor::DISPLAYTERMS_KEY][TermIndexEntry::TYPE_LABEL] ) ?
			$searchResult[TermSearchInteractor::DISPLAYTERMS_KEY][TermIndexEntry::TYPE_LABEL] : null;

		$displayDescription = isset( $searchResult[TermSearchInteractor::DISPLAYTERMS_KEY][TermIndexEntry::TYPE_DESCRIPTION] ) ?
			$searchResult[TermSearchInteractor::DISPLAYTERMS_KEY][TermIndexEntry::TYPE_DESCRIPTION] : null;

		$matchedTerm = isset( $searchResult[TermSearchInteractor::MATCHEDTERM_KEY] ) ?
			$searchResult[TermSearchInteractor::MATCHEDTERM_KEY] : null;

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

		if ( $labelHtml !== '' || $descriptionHtml !== '' || $matchHtml !== '' )  {
			$result .= wfMessage( 'colon-separator' )->escaped();
		}

		if ( $labelHtml !== '' )  {
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

		$result = Html::rawElement( 'li', array( 'class' => 'wikibase-disambiguation' ), $result );
		return $result;
	}

	/**
	 * Returns HTML representing the label in the search language.
	 * The result will include the language's name in the user language.
	 *
	 * If the label is the same as the label already displayed by the formatted
	 * ItemID link then no additional label will be displayed
	 *
	 * @param Term|null $displayLabel
	 * @param Term $matchedTerm
	 *
	 * @return string HTML
	 */
	private function getLabelHtml( Term $label = null ) {
		if( !$label ) {
			return '';
		}

		//TODO: include actual language if $label is a FallbackTerm
		$labelElement = Html::element(
			'span',
			array( 'class' => 'wb-itemlink-label' ),
			$label->getText()
		);
		return $labelElement;
	}

	/**
	 * Returns HTML representing the description in the given language.
	 *
	 * @param Term|null $description
	 *
	 * @return string HTML
	 */
	private function getDescriptionHtml( Term $description = null ) {
		if( !$description ) {
			return '';
		}

		//TODO: include actual language if $description is a FallbackTerm
		$descriptionElement = Html::element(
			'span',
			array( 'class' => 'wb-itemlink-description' ),
			$description->getText()
		);
		return $descriptionElement;
	}

	/**
	 * Returns HTML representing the label in the search language.
	 * The result will include the language's name in the user language.
	 *
	 * If the label is the same as the label already displayed by the formatted
	 * ItemID link then no additional label will be displayed
	 *
	 * @param Term $match
	 * @param Term|null $label
	 *
	 * @return string HTML
	 */
	private function getMatchHtml( Term $match, Term $label = null ) {
		if( !$match ) {
			return '';
		}

		if( $label && $label->getText() == $match->getText() ) {
			return '';
		}

		$text = $match->getText();
		$language = $match->getLanguageCode();

		return wfMessage( 'wikibase-itemlink-userlang-wrapper' )->params( $language, $text )->parse();
	}

}
