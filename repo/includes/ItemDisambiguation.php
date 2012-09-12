<?php

namespace Wikibase;
use Language, IContextSource, MWException;

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

	/**
	 * @since 0.1
	 * @var string
	 */
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
	}

	protected static function getLangStore( $langCode ) {
		static $langStore = array();

		if ( !isset( $langStore[$langCode] ) ) {
			$langStore[$langCode] = array_merge( array( $langCode ), Language::getFallbacksFor( $langCode ) );
		}

		return $langStore[$langCode];
	}

	/**
	 * Builds and returns the HTML to represent the WikibaseItem.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getHTML() {

		$html = '';
		foreach ( $this->items as /* ItemContent */ $itemContent ) {
			$innerHtml = $this->getHtmlForLabel( $itemContent, self::getLangStore( $this->getLanguage()->getCode() ) );
			$innerHtml .= $this->getHtmlForLanguage( $itemContent, $this->langCode );
			$innerHtml .= $this->getHtmlForDescription( $itemContent, self::getLangStore( $this->getLanguage()->getCode() ) );

			$html .= \Html::rawElement(
				'li',
				array( 'class' => 'wikibase-disambiguation' ),
				$innerHtml
			);
		}

		return \Html::rawElement(
			'ul',
			array( 'class' => 'wikibase-disambiguation' ),
			$html
		);
	}

	/**
	 * Create HTML for display of the label in the item disambiguation list
	 *
	 * @since 0.1
	 */
	protected function getHtmlForLabel( $itemContent, $userLanguages ) {

		// Get the label for the first languages on the chain
		// that doesn't fail, use a fallback if everything fails. This could
		// use the user supplied list of acceptable languages as a filter.
		list( $labelCode, $labelText, $labelLang) =
			Utils::lookupMultilangText(
				$itemContent->getItem()->getLabels( $userLanguages ),
				$userLanguages,
				array(
					$this->getLanguage()->getCode(),
					wfMessage( 'wikibase-itemlink-id-wrapper' )->params( $itemContent->getTitle()->getText() )->escaped(),
					$this->getLanguage()
				)
			);

		// Return the initial link in the users own language
		return
			\Linker::link(
				$itemContent->getTitle(),
				\Html::element(
					'span',
					array( 'class' => 'wb-itemlink-id', 'lang' => htmlspecialchars( $labelCode ) ),
					htmlspecialchars( $labelText )
				)
			);
	}

	/**
	 * Create HTML for display of the language bracket in the item disambiguation list
	 *
	 * @since 0.1
	 */
	protected function getHtmlForLanguage( $itemContent, $queryLanguage ) {

		// Return the link for the query language
		return
			\Html::rawElement(
				'span',
				array( 'class' => 'wb-itemlink-query-lang' ),
				wfMessage( 'wikibase-language-id-wrapper' )->rawParams(
					\Linker::link(
						$itemContent->getTitle(),
						htmlspecialchars( $queryLanguage ),
						array(),
						array( 'uselang' => htmlspecialchars( $queryLanguage ) )
					)
				)->escaped()
			);
	}

	/**
	 * Create HTML for display of the description in the item disambiguation list
	 *
	 * @since 0.1
	 */
	protected function getHtmlForDescription( $itemContent, $userLanguages ) {

		// Get the description for the first language found on the chain
		// that doesn't fail, use a fallback if everything fails. This could
		// use the user supplied list of acceptable languages as a filter.
		list( $descriptionCode, $descriptionText, $descriptionLang) =
			Utils::lookupMultilangText(
				$itemContent->getItem()->getDescriptions( $userLanguages ),
				$userLanguages,
				array(
					$this->getLanguage()->getCode(),
					'',
					$this->getLanguage()
				)
			);

		// Return the description in the users own language
		return
			\Html::rawElement(
				'span',
				array( 'class' => 'wb-itemlink-separator' ),
				wfMessage( 'colon-separator' )->escaped()
			)
			. \Html::element(
				'span',
				array(
					'class' => 'wb-itemlink-description',
					'lang' => $descriptionLang->getCode(),
					'dir' => $descriptionLang->getDir()
				),
				htmlspecialchars( $descriptionText )
			);
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