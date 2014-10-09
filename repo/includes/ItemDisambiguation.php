<?php

namespace Wikibase;

use Language;
use Wikibase\DataModel\Entity\Item;
use Wikibase\Lib\EntityIdFormatter;

/**
 * Class representing the disambiguation of a list of WikibaseItems.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author aude
 * @author jeblad
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class ItemDisambiguation {

	/**
	 * @var string
	 */
	protected $searchLangCode;

	/**
	 * @var string
	 */
	protected $userLangCode;

	/**
	 * @var EntityIdFormatter
	 */
	private $linkFormatter;

	/**
	 * Constructor.
	 *
	 * @since 0.5
	 *
	 * @param string $searchLangCode The language the search was performed for.
	 * @param string $userLangCode The user's interface language.
	 * @param EntityIdFormatter $linkFormatter A formatter for generating HTML links for a given EntityId.
	 */
	public function __construct( $searchLangCode, $userLangCode, EntityIdFormatter $linkFormatter ) {
		$this->searchLangCode = $searchLangCode;
		$this->userLangCode = $userLangCode;

		$this->linkFormatter = $linkFormatter;
	}

	/**
	 * Builds and returns the HTML to represent the WikibaseItem.
	 *
	 * @since 0.5
	 *
	 * @param Item[] $items
	 *
	 * @return string HTML
	 */
	public function getHTML( array $items ) {
		return
			'<ul class="wikibase-disambiguation">' .
				implode( '', array_map(
					array( $this, 'getItemHtml' ),
					$items
				) ).
			'</ul>';
	}

	/**
	 * @param Item $item
	 *
	 * @return string HTML
	 */
	public function getItemHtml( Item $item ) {
		$userLang = $this->userLangCode;
		$searchLang = $this->searchLangCode;

		$result = $this->linkFormatter->format( $item->getId() );

		// Display the label in the searched language in case it is different than in
		// the user language.
		if ( $userLang !== $searchLang
			&& $item->getLabel( $userLang ) !== $item->getLabel( $searchLang )  ) {
			$result .= $this->getLabelHtml( $item, $searchLang );
		};

		$result .= $this->getDescriptionHtml( $item, $userLang );

		$result = \Html::rawElement( 'li', array( 'class' => 'wikibase-disambiguation' ), $result );

		return $result;
	}

	/**
	 * Returns HTML representing the label in the given language.
	 * The result will include the language's name in the user language.
	 *
	 * @param Item $item
	 * @param string $language
	 *
	 * @return string HTML
	 */
	private function getLabelHtml( Item $item, $language ) {
		$label = $item->getLabel( $language );

		$labelElement = \Html::element(
			'span',
			array( 'class' => 'wb-itemlink-query-lang', 'lang' => $language ),
			$label
		);

		$msg = wfMessage( 'wikibase-itemlink-userlang-wrapper' )
			->rawParams(
				\Language::fetchLanguageName( $language, $this->userLangCode ),
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
	 * @param Item $item
	 * @param string $language
	 *
	 * @return string HTML
	 */
	private function getDescriptionHtml( Item $item, $language ) {

		// display the description in the user's language
		$description = $item->getDescription( $language );
		if ( $description === false || $description === '' ) {
			// Display the ID if no description is available
			// do not display it if the ID was already displayed, i.e. if it was used instead of the label previously
			$userLabel = $item->getLabel( $language );
			$idLabel = $item->getId()->getSerialization();

			$html = $userLabel ? ' ' . $idLabel : '';
		} else {
			$descriptionElement = \Html::element(
				'span',
				array( 'class' => 'wb-itemlink-description' ),
				$description
			);

			$html = htmlspecialchars( wfMessage( 'colon-separator' )->plain() )
				. $descriptionElement;
		}

		return $html;
	}

}
