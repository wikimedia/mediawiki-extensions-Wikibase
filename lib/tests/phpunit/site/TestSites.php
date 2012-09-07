<?php

/**
 * Holds sites for testing purposes.
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
 * @file
 * @since 1.20
 *
 * @ingroup Site
 * @ingroup Test
 *
 * @group Site
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class TestSites {

	/**
	 * @since 1.20
	 *
	 * @return array
	 */
	public static function getSites() {
		$sites = array();

		$site = Sites::newSite( 'foobar' );
		$site->setInternalId( 1 );
		$sites[] = $site;

		$site = Sites::newSite( 'enwiktionary' );
		$site->setInternalId( 2 );
		$site->setGroup( 'wiktionary' );
		$site->setType( Site::TYPE_MEDIAWIKI );
		$site->setLanguageCode( 'en' );
		$site->addNavigationId( 'enwiktionary' );
		$sites[] = $site;

		$site = Sites::newSite( 'dewiktionary' );
		$site->setInternalId( 3 );
		$site->setGroup( 'wiktionary' );
		$site->setType( Site::TYPE_MEDIAWIKI );
		$site->setLanguageCode( 'de' );
		$site->addInterwikiId( 'dewiktionary' );
		$site->addInterwikiId( 'wiktionaryde' );
		$sites[] = $site;

		$site = Sites::newSite( 'spam' );
		$site->setInternalId( 4 );
		$site->setGroup( 'spam' );
		$site->setType( Site::TYPE_UNKNOWN );
		$site->setLanguageCode( 'en' );
		$site->addNavigationId( 'spam' );
		$site->addNavigationId( 'spamz' );
		$site->addInterwikiId( 'spamzz' );
		$sites[] = $site;

		$id = 5;

		foreach ( array( 'en', 'de', 'nl', 'sv', 'sr', 'no', 'nn' ) as $langCode ) {
			$site = Sites::newSite( $langCode . 'wiki' );
			$site->setInternalId( $id++ );
			$site->setGroup( 'wikipedia' );
			$site->setType( Site::TYPE_MEDIAWIKI );
			$site->setLanguageCode( $langCode );
			$site->addInterwikiId( $langCode );
			$site->addNavigationId( $langCode );
			$sites[] = $site;
		}

		return $sites;
	}

	/**
	 * Inserts sites into the database for the unit tests that need them.
	 *
	 * @since 0.1
	 */
	public static function insertIntoDb() {
		$dbw = wfGetDB( DB_MASTER );

		$dbw->begin( __METHOD__ );

		$dbw->delete( 'sites', '*', __METHOD__ );

		/**
		 * @var \Site $site
		 */
		foreach ( \TestSites::getSites() as $site ) {
			$site->save();
		}

		$dbw->commit( __METHOD__ );

		Sites::singleton()->getSites( false ); // re-cache
	}

}