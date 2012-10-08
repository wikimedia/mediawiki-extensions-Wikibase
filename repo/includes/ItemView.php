<?php

namespace Wikibase;
use Html, ParserOptions, ParserOutput, Title, Language, IContextSource, OutputPage, Sites, Site, MediaWikiSite;

/**
 * Class for creating views for Wikibase\Item instances.
 * For the Wikibase\Item this basically is what the Parser is for WikitextContent.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author H. Snater
 * @author Daniel Werner
 * @author Tobias Gritschacher
 * @author Daniel Kinzler
 */
class ItemView extends EntityView {

	const VIEW_TYPE = 'item';

	/**
	 * @see EntityView::getInnerHtml
	 */
	public function getInnerHtml( EntityContent $entity, Language $lang = null, $editable = true ) {
		$html = parent::getInnerHtml( $entity, $lang, $editable );

		// add site-links to default entity stuff
		$html .= $this->getHtmlForSiteLinks( $entity, $lang, $editable );

		return $html;
	}

	/**
	 * Builds and returns the HTML representing a WikibaseEntity's site-links.
	 *
	 * @since 0.1
	 *
	 * @param EntityContent $item the entity to render
	 * @param Language|null $lang the language to use for rendering. if not given, the local context will be used.
	 * @param bool $editable whether editing is allowed (enabled edit links)
	 * @return string
	 */
	public function getHtmlForSiteLinks( EntityContent $item, Language $lang = null, $editable = true ) {
		$info = $this->extractEntityInfo( $item, $lang );
		$siteLinks = $item->getItem()->getSiteLinks();
		$html = '';

		$html .= Html::element( 'h2', array( 'class' => 'wb-sitelinks-heading' ), wfMessage( 'wikibase-sitelinks' ) );

		$html .= Html::openElement( 'table', array( 'class' => 'wb-sitelinks' ) );

		$html .= Html::openElement( 'colgroup' );
		$html .= Html::element( 'col', array( 'class' => 'wb-sitelinks-sitename' ) );
		$html .= Html::element( 'col', array( 'class' => 'wb-sitelinks-siteid' ) );
		$html .= Html::element( 'col', array( 'class' => 'wb-sitelinks-link' ) );
		$html .= Html::element( 'col', array(
			'class' => 'wb-ui-propertyedittool-editablevalue-toolbarparent'
		) );
		$html .= Html::closeElement( 'colgroup' );

		if( !empty( $siteLinks ) ) {

			$html .= Html::openElement( 'thead' );

			$html .= Html::openElement( 'tr', array( 'class' => 'wb-sitelinks-columnheaders' ) );
			$html .= Html::element(
				'th',
				array( 'class' => 'wb-sitelinks-sitename' ),
				wfMessage( 'wikibase-sitelinks-sitename-columnheading' )
			);
			$html .= Html::element(
				'th',
				array( 'class' => 'wb-sitelinks-siteid' ),
				wfMessage( 'wikibase-sitelinks-siteid-columnheading' )
			);
			$html .= Html::element(
				'th',
				array( 'class' => 'wb-sitelinks-link' ),
				wfMessage( 'wikibase-sitelinks-link-columnheading' )
			);
			$html .= Html::element( 'th' );
			$html .= Html::closeElement( 'tr' );

			$html .= Html::closeElement( 'thead' );

		}

		$i = 0;

		// Batch load the sites we need info about during the building of the sitelink list.
		Sites::singleton()->getSites();

		// Sort the sitelinks according to their global id
		$safetyCopy = $siteLinks; // keep a shallow copy;
		$sortOk = usort(
			$siteLinks,
			function( $a, $b ) {
				return strcmp($a->getSite()->getGlobalId(), $b->getSite()->getGlobalId() );
			}
		);
		if ( !$sortOk ) {
			$siteLinks = $safetyCopy;
		}

		/**
		 * @var SiteLink $link
		 */
		foreach( $siteLinks as $link ) {
			$alternatingClass = ( $i++ % 2 ) ? 'even' : 'uneven';

			$site = $link->getSite();

			if ( $site->getDomain() === '' ) {
				// the link is pointing to an unknown site.
				// XXX: hide it? make it red? strike it out?

				$html .= Html::openElement( 'tr', array(
						'class' => 'wb-sitelinks-site-unknown ' . $alternatingClass )
				);

				$html .= Html::element(
					'td',
					array( 'colspan' => '2', 'class' => ' wb-sitelinks-sitename wb-sitelinks-sitename-unknown' ),
					$link->getSite()->getGlobalId()
				);

				$html .= Html::element(
					'td',
					array( 'class' => 'wb-sitelinks-link wb-sitelinks-link-broken' ),
					$link->getPage()
				);

				$html .= Html::closeElement( 'tr' );
			} else {
				$languageCode = $site->getLanguageCode();

				$html .= Html::openElement( 'tr', array(
						'class' => 'wb-sitelinks-' . $languageCode . ' ' . $alternatingClass )
				);

				$html .= Html::element(
					'td',
					array(
						'class' => ' wb-sitelinks-sitename wb-sitelinks-sitename-' . $languageCode
					),
					// TODO: get an actual site name rather then just the language
					Utils::fetchLanguageName( $languageCode )
				);

				$html .= Html::element(
					'td',
					array(
						'class' => ' wb-sitelinks-siteid wb-sitelinks-siteid-' . $languageCode,
						// site id language code is always ltr
						'dir' => 'ltr'
					),
					// TODO: get an actual site id rather then just the language code
					$languageCode
				);
				/* TODO: for non-JS, also set the dir attribute on the link cell;
				but do not build language objects for each site since it causes too much load
				and will fail when having too much site links */
				$html .= Html::openElement(
					'td',
					array(
						'class' => 'wb-sitelinks-link wb-sitelinks-link-' . $languageCode,
						'lang' => $languageCode
					)
				);

				$html .= Html::element(
					'a',
					array(
						'href' => $link->getUrl(),
						'dir' => 'auto'
					),
					$link->getPage()
				);
				$html .= Html::closeElement( 'td' );
				$html .= $this->getHtmlForEditSection( $item, $lang, 'td' );
				$html .= Html::closeElement( 'tr' );
			}
		}

		// add button
		$html .= Html::openElement( 'tfoot' );
		$html .= Html::openElement( 'tr' );
		$html .= Html::element( 'td', array( 'colspan' => '3', 'class' => 'wb-empty' ) );
		$html .= $this->getHtmlForEditSection( $item, $lang, 'td', 'add' );
		$html .= Html::closeElement( 'tr' );
		$html .= Html::closeElement( 'tfoot' );

		$html .= Html::closeElement( 'table' );

		return $html . Html::closeElement( 'div' ); // close .wb-item
	}

}
