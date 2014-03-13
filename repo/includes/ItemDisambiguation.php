<?php

namespace Wikibase;

use IContextSource;

/**
 * Class representing the disambiguation of a list of WikibaseItems.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author aude
 * @author jeblad
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ItemDisambiguation extends \ContextSource {

	/**
	 * @since 0.1
	 * @var Item
	 */
	protected $items;

	protected $langCode;

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 *
	 * @param ItemContent[] $items
	 * @param string $langCode
	 * @param IContextSource|null $context
	 *
	 * @thorws MWException
	 */
	public function __construct( array $items, $langCode, IContextSource $context = null ) {
		$this->items = $items;
		$this->langCode = $langCode;

		if ( !is_null( $context ) ) {
			$this->setContext( $context );
		}
	}

	/**
	 * Builds and returns the HTML to represent the WikibaseItem.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getHTML() {
		$userLang = $this->getLanguage()->getCode();
		$searchLang = $this->langCode;

		return
			'<ul class="wikibase-disambiguation">' .
				implode( '', array_map(
					function( ItemContent $itemContent ) use ( $userLang, $searchLang ) {

						$userLabel = $itemContent->getItem()->getLabel( $userLang );
						$searchLabel = $itemContent->getItem()->getLabel( $searchLang );

						// link to the item. The text is the label in the user's language, or the ID if no label exists
						$idLabel = \Html::openElement( 'span', array( 'class' => 'wb-itemlink-id' ) )
							. wfMessage( 'wikibase-itemlink-id-wrapper' )->params(
								$itemContent->getEntity()->getId()->getSerialization() )->escaped()
							. \Html::closeElement( 'span' );
						$result =
							\Linker::link(
								$itemContent->getTitle(),
								$userLabel ? htmlspecialchars( $userLabel ) : $idLabel
							);

						// display the label in the searched language in case it is different than in the user language
						if ( ( $userLang !== $searchLang ) && ( $userLabel !== $searchLabel ) ) {
							$result = $result
								. wfMessage( 'wikibase-itemlink-userlang-wrapper' )
									->rawParams(
										\Language::fetchLanguageName( $searchLang, $userLang ),
										\Html::element(
											'span',
											array( 'class' => 'wb-itemlink-query-lang', 'lang' => $searchLang ),
											$searchLabel
										)
									)
									->parse();
						};

						// display the description in the user's language
						$description = htmlspecialchars( $itemContent->getItem()->getDescription( $userLang ) );
						if ( $description === "" ) {
							// Display the ID if no description is available
							// do not display it if the ID was already displayed, i.e. if it was used instead of the label previously
							$result .= $userLabel ? ' ' . $idLabel : '';
						} else {
							$result .=
								wfMessage( 'colon-separator' )->escaped()
								. \Html::openElement( 'span', array( 'class' => 'wb-itemlink-description' ) )
								. $description
								. \Html::closeElement( 'span' );
						}

						$result = \Html::rawElement( 'li', array( 'class' => 'wikibase-disambiguation' ), $result );
						return $result;
					},
					$this->items
				) ).
			'</ul>';
	}

	/**
	 * Display the item using the set context.
	 *
	 * @since 0.1
	 */
	public function display() {
		$this->getOutput()->addHTML( $this->getHTML() );
	}

}
