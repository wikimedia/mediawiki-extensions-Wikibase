<?php

namespace Wikibase;

use Html;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\EntityIdFormatter;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Repo\Interactors\TermSearchInteractor;

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
	 * @param array[] $searchResults as returned by TermSearchInteractor
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
	 * @param array[] $searchResult
	 *
	 * @return string HTML
	 */
	public function getResultHtml( array $searchResult ) {
		$result = $this->linkFormatter->formatEntityId( $searchResult['entityId'] );
		$result .= $this->getLabelHtml(
			$searchResult[TermSearchInteractor::DISPLAYTERMS_KEY],
			$searchResult[TermSearchInteractor::MATCHEDTERM_KEY]
		);
		$result .= $this->getDescriptionHtml(
			$searchResult[TermSearchInteractor::DISPLAYTERMS_KEY],
			$searchResult[TermSearchInteractor::ENTITYID_KEY]
		);
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
	 * @param Term[] $displayTerms
	 * @param Term $matchedTerm
	 *
	 * @return string HTML
	 */
	private function getLabelHtml( $displayTerms, $matchedTerm ) {
		if( array_key_exists( TermIndexEntry::TYPE_LABEL, $displayTerms ) ) {
			$displayLabel = $displayTerms[TermIndexEntry::TYPE_LABEL];
		}
		if( isset( $displayLabel ) && $displayLabel->getText() == $matchedTerm->getText() ) {
			return '';
		}
		$label = $matchedTerm->getText();
		$language = $matchedTerm->getLanguageCode();
		$labelElement = Html::element(
			'span',
			array( 'class' => 'wb-itemlink-query-lang', 'lang' => $language ),
			$label
		);
		$msg = wfMessage( 'wikibase-itemlink-userlang-wrapper' )
			->rawParams(
				$this->languageNameLookup->getName( $language, $this->displayLanguageCode ),
				$labelElement
			);
		return $msg->parse();
	}

	/**
	 * Returns HTML representing the description in the given language.
	 * If no description is defined in that language, return the item's ID,
	 * unless the label is not defined either. In that case, this method
	 * returns an empty string, because the entity ID was already used as
	 * a label.
	 *
	 * @param Term[] $displayTerms
	 * @param string $entityId
	 *
	 * @return string HTML
	 */
	private function getDescriptionHtml( $displayTerms, $entityId ) {
		if ( isset( $displayTerms[TermIndexEntry::TYPE_DESCRIPTION] ) ) {
			$descriptionElement = Html::element(
				'span',
				array( 'class' => 'wb-itemlink-description' ),
				$displayTerms[TermIndexEntry::TYPE_DESCRIPTION]->getText()
			);
			return htmlspecialchars( wfMessage( 'colon-separator' )->plain() ) . $descriptionElement;
		} else {
			if ( array_key_exists( TermIndexEntry::TYPE_LABEL, $displayTerms ) ) {
				$entityIdElement = Html::element( 'span', array(), $entityId );
				return htmlspecialchars( wfMessage( 'colon-separator' )->plain() ) . $entityIdElement;
			}
			return '';
		}
	}

}
