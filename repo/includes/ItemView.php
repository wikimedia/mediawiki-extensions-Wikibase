<?php

namespace Wikibase;
use Html;

/**
 * Class for creating views for Wikibase\Item instances.
 * For the Wikibase\Item this basically is what the Parser is for WikitextContent.
 *
 * @since 0.1
 *
 * @file WikibaseItemView.php
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author H. Snater
 * @author Daniel Werner
 */
class ItemView extends \ContextSource {

	/**
	 * @since 0.1
	 * @var Item
	 */
	protected $item;

	/**
	 * Constructor.
	 *
	 * @todo think about using IContextSource here. Parser for example uses parser options (which also can be generated
	 *       from an IContextSource) but this seems sufficient for now.
	 *
	 * @since 0.1
	 *
	 * @param Item $item
	 * @param \IContextSource|null $context
	 */
	public function __construct( Item $item, \IContextSource $context = null ) {
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
		$lang = $this->getLanguage();

		$description = $this->item->getDescription( $lang->getCode() );
		$aliases = $this->item->getAliases( $lang->getCode() );
		$siteLinks = $this->item->getSiteLinks();
		
		// even if description is false, we want it in any case!
		$html .= Html::openElement( 'div', array( 'class' => 'wb-property-container' ) );
		$html .= Html::element( 'div', array( 'class' => 'wb-property-container-key', 'title' => 'description' ) );
		$html .= Html::element( 'span', array( 'class' => 'wb-property-container-value'), $description );
		$html .= Html::closeElement( 'div' );

		$html .= Html::element( 'hr', array( 'class' => 'wb-hr' ) );

		// ALIASES

		if( ! empty( $aliases ) ) {
			$html .= Html::openElement( 'div', array( 'class' => 'wb-aliases' ) );
			$html .= Html::element( 'span', array( 'class' => 'wb-aliases-label' ), wfMsg( 'wikibase-aliases-label' ) );
			$html .= Html::openElement( 'ul', array( 'class' => 'wb-aliases-container' ) );
			foreach( $aliases as $alias ) {
				$html .= Html::element(
					'li', array( 'class' => 'wb-aliases-alias' ), $alias
				);
			}
			$html .= Html::closeElement( 'ul' );
			$html .= Html::closeElement( 'div' );
		}

		// SITE-LINKS

		if( empty( $siteLinks ) ) {
			// no site links available for this item
			$html .= Html::element( 'div', array( 'class' => 'wb-sitelinks-empty' ), wfMessage( 'wikibase-sitelinks-empty' ) );
		} else {
			$html .= Html::openElement( 'table', array( 'class' => 'wb-sitelinks', 'cellspacing' => '0' ) );

			$html .= Html::openElement( 'thead' );
			$html .= Html::openElement( 'tr' );
			$html .= Html::openElement( 'th', array( 'colspan' => '2' ) );
			$html .= Html::element( 'h3', array(), wfMessage( 'wikibase-sitelinks' ) );
			$html .= Html::closeElement( 'th' );
			$html .= Html::closeElement( 'tr' );
			$html .= Html::closeElement( 'thead' );

			$i = 0;
			foreach( $siteLinks as $siteId => $title ) {
				$alternatingClass = ( $i++ % 2 ) ? 'even' : 'uneven';
				$html .= Html::openElement( 'tr', array(
					'class' => 'wb-sitelinks-' . $siteId . ' ' . $alternatingClass )
				);
				$html .= Html::element(
						'td', array( 'class' => ' wb-sitelinks-site wb-sitelinks-site-' . $siteId ),
						// TODO get the site name instead of pretending the ID is a lang code and the sites name a language!
						\Language::fetchLanguageName( $siteId ) . ' (' . $siteId . ')'
				);
				$html .= Html::openElement( 'td', array( 'class' => 'wb-sitelinks-link wb-sitelinks-link-' . $siteId ) );
				$html .= Html::element(
					'a',
					array( 'href' => Sites::singleton()->getUrl( $siteId, $title ) ),
					$title
				);
				$html .= Html::closeElement( 'td' );
				$html .= Html::closeElement( 'tr' );
			}
			$html .= Html::closeElement( 'table' );
		}

		return $html;
	}

	/**
	 * Returns a ParserOutput object with the rendered item for the provided context source
	 *
	 * @since 0.1
	 *
	 * @return ParserOutput
	 */
	public function render() {
		$langCode = $this->getLanguage()->getCode();

		// fresh parser output with items markup
		$out = new \ParserOutput( $this->getHTML() );

		#@todo (phase 2) would be nice to put pagelinks (item references) and categorylinks (from special properties)...
		#@todo:          ...as well as languagelinks/sisterlinks into the ParserOutput.

		// make css available for JavaScript-less browsers
		$out->addModuleStyles( array( 'wikibase.common' ) );

		// make sure required client sided resources will be loaded:
		$out->addModules( 'wikibase.ui.PropertyEditTool' );

		// NOTE: instead of calling $this->getOutput(), at least addJsConfigVars() could be implemented into ParserOutput,
		//       right now it is only available in the OutputPage class, using this here might be kind of hacky.

		// overwrite page title
		$this->getOutput()->setPageTitle( $this->item->getLabel( $langCode ) );

		// hand over the itemId to JS
		$this->getOutput()->addJsConfigVars( 'wbItemId', $this->item->getId() );
		$this->getOutput()->addJsConfigVars( 'wbDataLangName', \Language::fetchLanguageName( $langCode ) );

		// TODO: this whole construct doesn't really belong here:
		$sites = array();
		foreach ( Sites::singleton()->getGroup( 'wikipedia' ) as /* Wikibase\Site */ $site ) {
			$sites[$site->getId()] = array(
				'shortName' => \Language::fetchLanguageName( $site->getId() ),
				'name' => \Language::fetchLanguageName( $site->getId() ), // TODO: names should be configurable in settings
				'pageUrl' => $site->getPageUrlPath(),
				'apiUrl' => $site->getPath( 'api.php' ),
			);
		}
		$this->getOutput()->addJsConfigVars( 'wbSiteDetails', $sites );

		return $out;
	}

}