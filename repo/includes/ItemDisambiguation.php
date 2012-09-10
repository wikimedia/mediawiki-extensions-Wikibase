<?php

namespace Wikibase;
use IContextSource, MWException;

/**
 * Class representing the disambiguation of a list of WikibaseItems.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
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
	 * @param $items array of ItemContent
	 * @param string $langCode
	 * @param $context IContextSource|null
	 *
	 * @thorws MWException
	 */
	public function __construct( array $items, $langCode, IContextSource $context = null ) {
		$this->items = $items;
		$this->langCode = $langCode;

		if ( !is_null( $context ) ) {
			$this->setContext( $context );
		}

		//if ( count( $this->items ) < 2 ) {
		//	throw new MWException( 'Cannot disambiguate less then 2 items!' );
		//}
	}

	/**
	 * Builds and returns the HTML to represent the WikibaseItem.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getHTML() {
		//global $wgContLang;
		$langCode = $this->langCode;

		return
			'<ul class="wikibase-disambiguation">' .
				implode( '', array_map(
					function( ItemContent $item ) use ( $langCode ) {
						global $wgLang;
						// Figure out which description to use while identifying the item
						list( $descriptionCode, $descriptionText, $descriptionLang) =
							\Wikibase\Utils::lookupUserMultilangText(
								$item->getItem()->getDescriptions(),
								\Wikibase\Utils::languageChain( $langCode ),
								array( $langCode, '', \Language::factory( $langCode ) )
							);
						return \Html::rawElement(
							'li',
							array( 'class' => 'wikibase-disambiguation' ),
							\Linker::link(
								$item->getTitle(),
								// FIXME: Need a more general way to figure out the "q" thingy.
								// This should REALLY be something more elegant, but it is sufficient for now.
								\Html::openElement( 'span', array( 'class' => 'wb-itemlink-id' ) )
								. $item->getItem()->getLabel( $langCode  ) ? $item->getItem()->getLabel( $langCode  ) : wfMessage( 'wikibase-itemlink-id-wrapper' )->params( 'q' . $item->getItem()->getId() )->escaped()
								. \Html::closeElement( 'span' )
							)
							. wfMessage( 'colon-separator' )->escaped()
							. \Html::openElement( 'span', array( 'class' => 'wb-itemlink-description', 'lang' => $descriptionLang->getCode(), 'dir' => $descriptionLang->getDir() ) )
							. htmlspecialchars( $descriptionText )
							. \Html::closeElement( 'span' )
						);
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