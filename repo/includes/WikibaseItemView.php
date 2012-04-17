<?php

/**
 * Class for creation views of WikibaseItems.
 *
 * @since 0.1
 *
 * @file WikibaseItemView.php
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class WikibaseItemView extends ContextSource {

	/**
	 * @since 0.1
	 * @var WikibaseItem
	 */
	protected $item;

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 *
	 * @param WikibaseItem $item
	 * @param IContextSource|null $context
	 */
	public function __construct( WikibaseItem $item, IContextSource $context = null ) {
		$this->item = $item;

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
		$html = '';

		$description = $this->item->getDescription( $this->getLanguage()->getCode() );

		// even if description is false, we want it in any case!
		$html .= Html::openElement( 'div', array( 'class' => 'wb-property-container' ) );
		$html .= HTML::element( 'div', array( 'class' => 'wb-property-container-key', 'title' => 'description' ) );
		$html .= HTML::element( 'span', array( 'class' => 'wb-property-container-value'), $description );
		$html .= Html::closeElement('div');

		$html .= Html::openElement( 'table', array( 'class' => 'wikitable' ) );

		foreach ( $this->item->getSiteLinks() AS $siteId => $title ) {
			$html .= '<tr>';

			$html .= Html::element( 'td', array(), $siteId );

			$html .= '<td>';
			$html .= Html::element(
				'a',
				array( 'href' => WikibaseUtils::getSiteUrl( $siteId, $title ) ),
				$title
			);
			$html .= '</td>';

			$html .= '</tr>';
		}

		$html .= Html::closeElement( 'table' );

		$htmlTable = '';

		// TODO: implement real ui instead of debug code
		foreach ( WikibaseContentHandler::flattenArray( $this->item->toArray() ) as $k => $v ) {
			$htmlTable .= Html::openElement( 'tr' );
			$htmlTable .= Html::element( 'td', null, $k );
			$htmlTable .= Html::element( 'td', null, $v );
			$htmlTable .= Html::closeElement( 'tr' );
		}

		$htmlTable = Html::rawElement( 'table', array('class' => 'wikitable'), $htmlTable );

		$html .= $htmlTable;

		return $html;
	}

}