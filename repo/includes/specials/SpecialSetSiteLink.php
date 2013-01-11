<?php

use Wikibase\SiteLink;

/**
 * Special page for setting the sitelink of a Wikibase entity.
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
class SpecialSetSiteLink extends SpecialSetEntity {

	public function __construct() {
		parent::__construct( 'SiteLink' );
	}

	/**
	 * @see SpecialSetEntity::getValue()
	 */
	protected function getValue( $entityContent, $language ) {
		if( !$entityContent ) {
			return '';
		}
		$sitelink = $entityContent->getEntity()->getSitelink( $language . 'wiki' );
		if( !$sitelink ) {
			return '';
		}
		return $sitelink->getPage();
	}

	/**
	 * @see SpecialSetEntity::setValue()
	 */
	protected function setValue( $entityContent, $language, $value ) {
		$site = \Sites::singleton()->getSite( $language . 'wiki' );
		$status = \Status::newGood();

		if( $site === false ) {
			$status->error( 'wikibase-setentity-invalid-langcode', $language ); // should not happen (strike out?)
			return $status;
		}
		if ( $site->normalizePageName( $value ) === false && $value !== '' ) {
			$status->error( 'wikibase-error-ui-no-external-page' );
			return $status;
		}

		if( $value === '' ) {
			$entityContent->getEntity()->removeSitelink( $language . 'wiki' );
		}
		else {
			$siteLink = new SiteLink( $site, $value );
			$entityContent->getEntity()->addSiteLink( $siteLink, 'set' );
		}
		return $status;
	}
}