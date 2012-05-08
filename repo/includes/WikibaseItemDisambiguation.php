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

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 *
	 * @param WikibaseItem $items
	 * @param IContextSource|null $context
	 */
	public function __construct( WikibaseItem $items, IContextSource $context = null ) {
		$this->items = $items;

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
		$html = '';

		foreach ( $this->items as /* WikibaseItem */ $item ) {

		}

		return $html;
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