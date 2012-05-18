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
 * @author H. Snater
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
		$siteLinks = $this->item->getSiteLinks();
		$lang = $this->getLanguage();
		$html = '';

		$description = $this->item->getDescription( $lang->getCode() );
		
		// even if description is false, we want it in any case!
		$html .= Html::openElement( 'div', array( 'class' => 'wb-property-container' ) );
		$html .= Html::element( 'div', array( 'class' => 'wb-property-container-key', 'title' => 'description' ) );
		$html .= Html::element( 'span', array( 'class' => 'wb-property-container-value'), $description );
		$html .= Html::closeElement( 'div' );
		
		if( empty( $siteLinks ) ) {
			// no site links available for this item
			$html .= Html::element( 'div', array( 'class' => 'wb-sitelinks-empty' ), wfMessage( 'wikibase-sitelinks-empty' ) );
		} else {
			$html .= Html::openElement( 'table', array( 'class' => 'wb-sitelinks', 'cellspacing' => '0' ) );
			$html .= Html::openElement( 'thead' );
			$html .= Html::openElement( 'tr' );
			$html .= Html::element( 'th', array( 'colspan' => '2' ), wfMessage( 'wikibase-sitelinks' ) );
			$html .= Html::closeElement( 'tr' );
			$html .= Html::closeElement( 'thead' );

			$i = 0;
			foreach( $siteLinks as $siteId => $title ) {
				$alternatingClass = ( $i++ % 2 ) ? 'even' : 'uneven';
				$html .= Html::openElement( 'tr', array(
					'class' => 'wb-sitelinks-' . $siteId . ' ' . $alternatingClass )
				);
				$html .= Html::element(
						'td', array( 'class' => 'wb-sitelinks-site-' . $siteId ),
						// TODO get the site name instead of pretending the ID is a lang code and the sites name a language!
						Language::fetchLanguageName( $siteId ) . ' (' . $siteId . ')'
				);
				$html .= Html::openElement( 'td', array( 'class' => 'wb-sitelinks-link-' . $siteId ) );
				$html .= Html::element(
					'a',
					array( 'href' => WikibaseSites::singleton()->getUrl( $siteId, $title ) ),
					$title
				);
				$html .= Html::closeElement( 'td' );
				$html .= Html::closeElement( 'tr' );
			}
			$html .= Html::closeElement( 'table' );
		}
		
		/*
		$html .= Html::element( 'div', array( 'style' => 'clear:both;' ) );
		$htmlTable = '';
		foreach ( WikibaseContentHandler::flattenArray( $this->item->toArray() ) as $k => $v ) {
			$htmlTable .= Html::openElement( 'tr' );
			$htmlTable .= Html::element( 'td', null, $k );
			$htmlTable .= Html::element( 'td', null, $v );
			$htmlTable .= Html::closeElement( 'tr' );
		}
		$htmlTable = Html::rawElement( 'table', array('class' => 'wikitable'), $htmlTable );
		$html .= $htmlTable;
		 */
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