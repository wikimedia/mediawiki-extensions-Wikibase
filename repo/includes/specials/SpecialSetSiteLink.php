<?php

use Wikibase\SiteLink;
use Wikibase\Utils;

/**
 * Special page for setting the sitepage of a Wikibase entity.
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
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@googlemail.com >
 */
class SpecialSetSiteLink extends SpecialModifyEntity {

	/**
	 * The site of the site link.
	 *
	 * @since 0.4
	 *
	 * @var string
	 */
	protected $site;

	/**
	 * The page of the site link.
	 *
	 * @since 0.4
	 *
	 * @var string
	 */
	protected $page;

	/**
	 * Constructor
	 *
	 * @since 0.4
	 */
	public function __construct() {
		parent::__construct( 'SetSiteLink' );
	}

	/**
	 * @see SpecialModifyEntity::prepareArguments()
	 *
	 * @since 0.4
	 *
	 * @param string $subPage
	 */
	protected function prepareArguments( $subPage ) {
		parent::prepareArguments( $subPage );

		$request = $this->getRequest();
		// explode the sub page from the format Special:SetSitelink/q123/enwiki
		$parts = ( $subPage === '' ) ? array() : explode( '/', $subPage, 2 );

		// site
		$this->site = $request->getVal( 'site', isset( $parts[1] ) ? $parts[1] : '' );

		if ( $this->site === '' ) {
			$this->site = null;
		}

		if ( !$this->isValidSiteId( $this->site ) && $this->site !== null ) {
			$this->showErrorHTML( $this->msg( 'wikibase-setsitelink-invalid-site', $this->site )->parse() );
		}

		// title
		$this->page = $request->getVal( 'page' );
	}

	/**
	 * @see SpecialModifyEntity::modifyEntity()
	 *
	 * @since 0.4
	 *
	 * @return string|boolean The summary or false
	 */
	protected function modifyEntity() {
		$request = $this->getRequest();
		// has to be checked before modifying but is no error
		if ( $this->entityContent === null || !$this->isValidSiteId( $this->site ) || !$request->wasPosted() ) {
			$this->showRightsMessage();

			return false;
		}

		// to provide removing after posting the full form
		if ( $request->getVal( 'remove' ) === null && $this->page === '' ) {
			$this->showErrorHTML(
				$this->msg(
					'wikibase-setsitelink-warning-remove',
					$this->entityContent->getTitle()->getText()
				)->parse(),
				'warning'
			);
			return false;
		}

		$status = $this->setSiteLink( $this->entityContent, $this->site, $this->page, $summary );

		if ( !$status->isGood() ) {
			$this->showErrorHTML( $status->getHTML() );
			return false;
		}

		return $summary;
	}

	/**
	 * Checks if the site id is valid.
	 *
	 * @since 0.4
	 *
	 * @param $siteId string the site id
	 *
	 * @return bool
	 */
	private function isValidSiteId( $siteId ) {
		return $siteId !== null && \Sites::singleton()->getSite( $siteId ) !== null;
	}

	/**
	 * @see SpecialModifyEntity::getFormElements()
	 *
	 * @since 0.4
	 *
	 * @return string
	 */
	protected function getFormElements() {
		if ( $this->page === null ) {
			$this->page = $this->getSiteLink( $this->entityContent, $this->site );
		}
		$pageinput = Html::input(
			'page',
			$this->page,
			'text',
			array(
				'class' => 'wb-input wb-input-text',
				'id' => 'wb-setsitelink-page',
				'size' => 50
			)
		)
		. Html::element( 'br' );

		$site = \Sites::singleton()->getSite( $this->site );

		if ( $this->entityContent !== null && $this->site !== null && $site !== null ) {
			return Html::rawElement(
				'p',
				array(),
				$this->msg(
					'wikibase-setsitelink-introfull',
					$this->entityContent->getTitle()->getPrefixedText(),
					'[' . $site->getPageUrl( '' ) . ' ' . $this->site . ']'
				)->parse()
			)
			. Html::input( 'site', $this->site, 'hidden' )
			. Html::input( 'id', $this->entityContent->getTitle()->getText(), 'hidden' )
			. Html::input( 'remove', 'remove', 'hidden' )
			. $pageinput;
		}
		else {
			return Html::element(
				'p',
				array(),
				$this->msg( 'wikibase-setsitelink-intro' )->text()
			)
			. parent::getFormElements()
			. Html::element(
				'label',
				array(
					'for' => 'wb-setsitelink-site',
					'class' => 'wb-label'
				),
				$this->msg( 'wikibase-setsitelink-site' )->text()
			)
			. Html::input(
				'site',
				$this->site,
				'text',
				array(
					'class' => 'wb-input',
					'id' => 'wb-setsitelink-site'
				)
			)
			. Html::element( 'br' )
			. Html::element(
				'label',
				array(
					'for' => 'wb-setsitelink-page',
					'class' => 'wb-label'
				),
				$this->msg( 'wikibase-setsitelink-label' )->text()
			)
			. $pageinput;
		}
	}

	/**
	 * Returning the site page of the entity.
	 *
	 * @since 0.4
	 *
	 * @param \Wikibase\EntityContent $entityContent
	 * @param string $site
	 *
	 * @return string
	 */
	protected function getSiteLink( $entityContent, $site ) {
		if ( $entityContent === null ) {
			return '';
		}
		$sitelink = $entityContent->getEntity()->getSitelink( $site );
		if ( $sitelink === null ) {
			return '';
		}
		return $sitelink->getPage();
	}

	/**
	 * Setting the sitepage of the entity.
	 *
	 * @since 0.4
	 *
	 * @param \Wikibase\EntityContent $entityContent
	 * @param string $site
	 * @param string $page
	 * @param string &$summary The summary for this edit will be saved here.
	 *
	 * @return Status
	 */
	protected function setSiteLink( $entityContent, $site, $page, &$summary ) {
		$siteObject = \Sites::singleton()->getSite( $site );
		$status = \Status::newGood();

		if ( $siteObject === null ) {
			$status->error( 'wikibase-setsitelink-invalid-site', $site );
			return $status;
		}

		// empty page means remove site link
		if ( $page === '' ) {
			$link = $entityContent->getItem()->getSiteLink( $site );
			if ( !$link ) {
				$status->error( 'wikibase-setsitelink-remove-failed' );
				return $status;
			}
			$entityContent->getItem()->removeSitelink( $site );
			$i18n = 'wbsetsitelink-remove';
		}
		else {
			// Try to normalize the page name
			$page = $siteObject->normalizePageName( $page );
			if ( $page === false ) {
				$status->error( 'wikibase-error-ui-no-external-page' );
				return $status;
			}
			$siteLink = new SiteLink( $siteObject, $page );
			$ret = $entityContent->getItem()->addSiteLink( $siteLink, 'set' );
			if ( $ret === false ) {
				$status->error( 'wikibase-setsitelink-add-failed' );
				return $status;
			}
			$i18n = 'wbsetsitelink-set';
		}
		$summary = $this->getSummary( $site, $page, $i18n ); // $summary is passed by reference ( &$summary )
		return $status;
	}
}
