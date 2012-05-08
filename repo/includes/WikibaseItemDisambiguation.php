<?php

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
class WikibaseItemDisambiguation extends ContextSource {

	/**
	 * @since 0.1
	 * @var WikibaseItem
	 */
	protected $items;

	protected $langCode;

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 *
	 * @param $items array of WikibaseItem
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

		if ( count( $this->items ) < 2 ) {
			throw new MWException( 'Cannot disambiguate less then 2 items!' );
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
		$langCode = $this->langCode;

		return
			'<ul class="wikibase-disambiguation">' .
				implode( '', array_map(
					function( WikibaseItem $item ) use ( $langCode ) {
						return HTML::rawElement(
							'li',
							array( 'class' => 'wikibase-disambiguation' ),
							Linker::link(
								$item->getTitle(),
								// TODO: rem label and have description fallback
								htmlspecialchars( $item->getLabel( $langCode ) . ': ' . $item->getDescription( $langCode ) )
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