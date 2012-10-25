<?php

namespace Wikibase;
use Html, ParserOutput, Title, Language, OutputPage, Sites, MediaWikiSite;

/**
 * Class for creating views for Wikibase\Item instances.
 * For the Wikibase\Item this basically is what the Parser is for WikitextContent.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
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

		// add statements to default entity stuff
		// TODO: Remove the setting when this goes live
		if ( Settings::get( 'uiWithStatements' ) ) {
			$html .= $this->getHtmlForStatements( $entity, $lang, $editable );
		}

		// add site-links to default entity stuff
		$html .= $this->getHtmlForSiteLinks( $entity, $lang, $editable );

		return $html;
	}

	/**
	 * Builds and returns the HTML representing a WikibaseEntity's statements.
	 *
	 * @since 0.1
	 *
	 * @param EntityContent $item the entity to render
	 * @param Language|null $lang the language to use for rendering. if not given, the local context will be used.
	 * @param bool $editable whether editing is allowed (enabled edit links)
	 * @return string
	 */
	public function getHtmlForStatements( EntityContent $item, Language $lang = null, $editable = true ) {
		global $wgLang;
		// TODO: Get rid of this
		if ( !isset( $lang ) ) {
			$lang = $wgLang;
		}
		$statements = $item->getItem()->getStatements();
		$html = '&nbsp;';

		$html .= Html::element( 'h2', array( 'class' => 'wb-statements-heading' ), wfMessage( 'wikibase-statements' ) );

		$i = 0;

		/**
		 * @var SiteLink $link
		 */
		foreach( $statements as $statement ) {
			//$alternatingClass = ( $i++ % 2 ) ? 'even' : 'uneven';

			$languageCode = $lang->getCode();
			$property = EntityContentFactory::singleton()->getFromId(Property::ENTITY_TYPE, $statement->getPropertyId());
			$link = \Linker::link(
				$property->getTitle(),
				htmlspecialchars( $property->getEntity()->getLabel($languageCode) )
			);

			// TODO: for non-JS, also set the dir attribute on the link cell;
			// but do not build language objects for each site since it causes too much load
			// and will fail when having too much site links
			$template = new Template( 'wb-statement', array(
				$statement->getGuid(),
				$link,
				Utils::fetchLanguageName( $languageCode ), // TODO: get an actual site name rather then just the language
				$this->getHtmlForEditSection( $item, $lang, 'div' )
			) );
			$html .= $template->text();
		}

		// add button
		$html .= Html::openElement( 'div' );
		$html .= $this->getHtmlForEditSection( $item, $lang, 'span', 'add' );
		$html .= Html::closeElement( 'div' );

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
		$siteLinks = $item->getItem()->getSiteLinks();
		$html = '';

		$html .= Html::element( 'h2', array( 'class' => 'wb-sitelinks-heading' ), wfMessage( 'wikibase-sitelinks' ) );

		$html .= Html::openElement( 'table', array( 'class' => 'wb-sitelinks' ) );

		$html .= Html::openElement( 'colgroup' );
		$html .= Html::element( 'col', array( 'class' => 'wb-sitelinks-sitename' ) );
		$html .= Html::element( 'col', array( 'class' => 'wb-sitelinks-siteid' ) );
		$html .= Html::element( 'col', array( 'class' => 'wb-sitelinks-link' ) );
		$html .= Html::element( 'col', array( 'class' => 'editsection' ) );
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
			$html .= Html::element(
				'th',
				array( 'class' => 'unsortable' ) // prevent column from being sortable
			);
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

				// TODO: for non-JS, also set the dir attribute on the link cell;
				// but do not build language objects for each site since it causes too much load
				// and will fail when having too much site links
				$template = new Template( 'wb-sitelink', array(
					$languageCode,
					$alternatingClass,
					Utils::fetchLanguageName( $languageCode ), // TODO: get an actual site name rather then just the language
					$languageCode, // TODO: get an actual site id rather then just the language code
					$link->getUrl(),
					$link->getPage(),
					$this->getHtmlForEditSection( $item, $lang, 'td' )
				) );
				$html .= $template->text();
			}
		}

		// built table footer with button to add site-links, consider list could be complete!
		$isFull = count( $siteLinks ) >= count( Sites::singleton()->getSites() );

		$html .= Html::openElement( 'tfoot' );

		// add button
		$html .= Html::openElement( 'tr' );
		$html .= Html::element(
			'td',
			array( 'colspan' => '3', 'class' => 'wb-sitelinks-placeholder' ),
			$isFull ? wfMessage( 'wikibase-sitelinksedittool-full' )->text() : ''
		);
		$html .= $this->getHtmlForEditSection( $item, $lang, 'td', 'add', !$isFull );
		$html .= Html::closeElement( 'tr' );
		$html .= Html::closeElement( 'tfoot' );

		$html .= Html::closeElement( 'table' );

		return $html;
	}

}
