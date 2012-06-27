<?php

namespace Wikibase;
use Html, ParserOptions, ParserOutput, Title, Language, IContextSource, OutputPage;

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
 * @author Tobias Gritschacher
 */
class ItemView extends \ContextSource {

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
	public function __construct( IContextSource $context = null ) {
		if ( !$context ) {
			$context = \RequestContext::getMain();
		}

		$this->setContext( $context );
	}

	/**
	 * Builds and returns the HTML to represent the WikibaseItem.
	 *
	 * @since 0.1
	 *
	 * @param \Wikibase\Item    $item the item to render
	 * @param \Language|null    $lang the language to use for rendering. if not given, the local context will be used.
	 * @param bool              $editable whether editing is allowed (enabled edit links).
	 *
	 * @return string
	 */
	public function getHTML( Item $item, Language $lang = null, $editable = true ) {
		//NOTE: even though $editable is unused at the moment, we will need it for the JS-less editing model.

		if ( !$lang ) {
			$lang = $this->getLanguage();
		}

		$html = '';

		$description = $item->getDescription( $lang->getCode() );
		$aliases = $item->getAliases( $lang->getCode() );
		$siteLinks = $item->getSiteLinks();

		// even if description is false, we want it in any case!
		$html .= Html::openElement( 'div', array( 'class' => 'wb-property-container' ) );
		$html .= Html::element( 'div', array( 'class' => 'wb-property-container-key', 'title' => 'description' ) );
		$html .= Html::element( 'span', array( 'class' => 'wb-property-container-value'), $description );
		$html .= Html::closeElement( 'div' );

		$html .= Html::element( 'hr', array( 'class' => 'wb-hr' ) );

		// ALIASES

		if ( empty( $aliases ) ) {
			// no aliases available for this item
			$html .= Html::element( 'div', array( 'class' => 'wb-aliases-empty' ), wfMessage( 'wikibase-aliases-empty' ) );
		} else {
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

			// Batch load the sites we need info about during the building of the sitelink list.
			Sites::singleton()->loadSites( array( 'global_key' => array_keys( $siteLinks ) ) );

			foreach( $siteLinks as $siteId => $title ) {
				$alternatingClass = ( $i++ % 2 ) ? 'even' : 'uneven';

				$site = Sites::singleton()->getSiteByGlobalId( $siteId );

				if ( $site === false ) {
					continue;
				}

				$languageCode = $site->getLanguage();

				$html .= Html::openElement( 'tr', array(
						'class' => 'wb-sitelinks-' . $languageCode . ' ' . $alternatingClass )
				);

				$html .= Html::element(
					'td',
					array(
						'class' => ' wb-sitelinks-site wb-sitelinks-site-' . $languageCode
					),
					// TODO: get an actual site name rather then just the language
					Utils::fetchLanguageName( $languageCode ) . ' (' . $languageCode . ')'
				);
				$html .= Html::openElement( 'td', array( 'class' => 'wb-sitelinks-link wb-sitelinks-link-' . $languageCode ) );
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

	protected function makeParserOptions( ) {
		$options = ParserOptions::newFromContext( $this );
		return $options;
	}

	/**
	 * Renders an item into an ParserOutput object
	 *
	 * @since 0.1
	 *
	 * @param Item                $item the item to analyze/render
	 * @param null|ParserOptions  $options parser options. If nto provided, the local context will be used to create generic parser options.
	 * @param bool                $generateHtml whether to generate HTML. Set to false if only interested in meta-info. default: true.
	 *
	 * @return ParserOutput
	 */
	public function getParserOutput( Item $item, ParserOptions $options = null, $generateHtml = true ) {
		if ( !$options ) {
			$options = $this->makeParserOptions();
		}

		$langCode = $options->getTargetLanguage();
		$editable = $options->getEditSection();

		// fresh parser output with items markup
		$pout = new ParserOutput();

		if ( $generateHtml ) {
			$html = $this->getHTML( $item, $langCode, $editable );
			$pout->setText( $html );
		}

		#@todo (phase 2) would be nice to put pagelinks (item references) and categorylinks (from special properties)...
		#@todo:          ...as well as languagelinks/sisterlinks into the ParserOutput.

		// make css available for JavaScript-less browsers
		$pout->addModuleStyles( array( 'wikibase.common' ) );

		if ( $editable ) {
			// make sure required client sided resources will be loaded:
			$pout->addModules( 'wikibase.ui.PropertyEditTool' );
		}

		//FIXME: some places, like Special:CreateItem, don't want to override the page title.
		//       But we still want to use OutputPage::addParserOutput to apply the modules etc from the ParserOutput.
		//       So, for now, we leave it to the caller to override the display title, if desired.
		// set the display title
		//$pout->setTitleText( $item->getLabel( $langCode ) );

		return $pout;
	}

	/**
	 * Outputs the given item to the OutputPage.
	 *
	 * @since 0.1
	 *
	 * @param \Wikibase\Item      $item the item to output
	 * @param null|OutputPage    $out the output page to write to. If not given, the local context will be used.
	 * @param null|ParserOptions $options parser options to use for rendering. If not given, the local context will be used.
	 * @param null|ParserOutput  $pout optional parser object - provide this if you already have a parser options for this item,
	 *                           to avoid redundant rendering.
	 *
	 * @return ParserOutput the parser output, for further processing.
	 * @todo: fixme: currently, only one item can be shown per page, because the item id is in a global JS config variable.
	 */
	public function render( Item $item, OutputPage $out = null, ParserOptions $options = null, ParserOutput $pout = null ) {
		if ( !$out ) {
			$out = $this->getOutput();
		}

		if ( !$pout ) {
			if ( !$options ) {
				$options = $this->makeParserOptions();
			}

			$pout = $this->getParserOutput( $item, $options, true );
		}

		if ( $options ) {
			$langCode = $options->getTargetLanguage();
		} else  {
			#XXX: this is quite ugly, we don't know that this language is the language that was used to generate the parser output object
			$langCode = $this->getLanguage()->getCode();
		}

		// overwrite page title
		$out->setPageTitle( $pout->getTitleText() );

		// hand over the itemId to JS
		$out->addJsConfigVars( 'wbItemId', $item->getId() );
		$out->addJsConfigVars( 'wbDataLangName', Utils::fetchLanguageName( $langCode ) );

		// TODO: this whole construct doesn't really belong here:
		$sites = array();

		foreach ( Sites::singleton()->getGroup( SITE_GROUP_WIKIPEDIA ) as  /** @var \Wikibase\Site $site */ $site ) {
			$languageName = Utils::fetchLanguageName( $site->getLanguage() );

			$sites[$site->getLanguage()] = array(
				'shortName' => $languageName,
				'name' => $languageName,
				'globalSiteId' => $site->getGlobalId(),
				'pageUrl' => $site->getPagePath(),
				'apiUrl' => $site->getFilePath( 'api.php' ),
			);
		}
		$out->addJsConfigVars( 'wbSiteDetails', $sites );

		$out->addParserOutput( $pout );
		return $pout;
	}

}