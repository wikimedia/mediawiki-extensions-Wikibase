<?php

namespace Wikibase;
use Language, IContextSource, MWException;
//use Title, Language, User, Revision, WikiPage, EditPage, ContentHandler, Html;

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

						// If this fails we will not find labels and descriptions later,
						// but we will try to get a list of alternate languages. The following
						// uses the user language as a starting point for the fallback chain.
						// It could be argued that the fallbacks should be limited to the user
						// selected languages.
						$lang = $wgLang->getCode();
						static $langStore = array();
						if ( !isset( $langStore[$lang] ) ) {
							$langStore[$lang] = array_merge( array( $lang ), Language::getFallbacksFor( $lang ) );
						}

						// Get the label and description for the first languages on the chain
						// that doesn't fail, use a fallback if everything fails. This could
						// use the user supplied list of acceptable languages as a filter.
						list( $labelCode, $labelText, $labelLang) = $labelTriplet =
							Utils::lookupMultilangText(
								$item->getItem()->getLabels( $langStore[$lang] ),
								$langStore[$lang],
								array(
									$wgLang->getCode(),
									wfMessage( 'wikibase-itemlink-id-wrapper' )->params( Settings::get( 'itemPrefix' ) . $item->getItem()->getId() )->escaped(),
									$wgLang
								)
							);
						list( $descriptionCode, $descriptionText, $descriptionLang) = $descriptionTriplet =
							Utils::lookupMultilangText(
								$item->getItem()->getDescriptions( $langStore[$lang] ),
								$langStore[$lang],
								array(
									$wgLang->getCode(),
									null,
									$wgLang
								)
							);

						// Format each entry
						return \Html::rawElement(
							'li',
							array( 'class' => 'wikibase-disambiguation' ),
							\Linker::link(
								$item->getTitle(),
								\Html::element(
									'span',
									array( 'class' => 'wb-itemlink-id', 'lang' => htmlspecialchars( $labelCode ) ),
									htmlspecialchars( $labelText )
								)
							)
							. \Html::openElement( 'span', array( 'class' => 'wb-itemlink-query-lang' ) )
							. wfMessage( 'wikibase-language-id-wrapper' )->rawParams(
								\Linker::link(
									$item->getTitle(),
									htmlspecialchars( $langCode ),
									array(),
									array( 'uselang' => htmlspecialchars( $langCode ) )
								)
							)->escaped()
							. \Html::closeElement( 'span' )
							. wfMessage( 'colon-separator' )->escaped()
							. \Html::element(
								'span',
								array(
									'class' => 'wb-itemlink-description',
									'lang' => $descriptionLang->getCode(),
									'dir' => $descriptionLang->getDir()
								),
								htmlspecialchars( $descriptionText )
							)
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