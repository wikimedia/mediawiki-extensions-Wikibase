<?php

/**
 * Class representing a single site that can be linked to.
 * TODO: investigate if we should not use wfUrlencode instead of rawurlencode.
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
 * @since 1.20
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Werner
 */
class SiteRow extends \ORMRow implements Site {

	/**
	 * Holds the local ids for this site.
	 * You can obtain them via @see getLocalIds
	 *
	 * @since 1.20
	 *
	 * @var array|false
	 */
	protected $localIds = false;

	/**
	 * @see Site::getConfig
	 *
	 * @since 1.20
	 *
	 * @return SiteConfig
	 */
	public function getConfig() {
		return new SiteConfigObject(
			$this->getField( 'local_key' ),
			$this->getField( 'link_inline' ),
			$this->getField( 'link_navigation' ),
			$this->getField( 'forward' )
		);
	}

	/**
	 * @see Site::getGlobalId
	 *
	 * @since 1.20
	 *
	 * @return string
	 */
	public function getGlobalId() {
		return $this->getField( 'global_key' );
	}

	/**
	 * @see Site::getType
	 *
	 * @since 1.20
	 *
	 * @return string
	 */
	public function getType() {
		return $this->getField( 'type' );
	}


	/**
	 * @see Site::getGroup
	 *
	 * @since 1.20
	 *
	 * @return string
	 */
	public function getGroup() {
		return $this->getField( 'group' );
	}

	/**
	 * @see Site::getSource
	 *
	 * @since 1.20
	 *
	 * @return string
	 */
	public function getSource() {
		return $this->getField( 'source' );
	}

	/**
	 * @see Site::getDomain
	 *
	 * @since 1.20
	 *
	 * @return string
	 */
	public function getDomain() {
		return substr( strrev( $this->getField( 'domain' ) ), 1 );
	}

	/**
	 * @see Site::getProtocol
	 *
	 * @since 1.20
	 *
	 * @return string
	 */
	public function getProtocol() {
		return $this->getField( 'protocol' );
	}

	/**
	 * @see Site::getPagePath
	 *
	 * @since 1.20
	 *
	 * @param string|false $pageName
	 *
	 * @return string
	 */
	public function getPagePath( $pageName = false ) {
		$pagePath = $this->getField( 'url' ) . $this->getField( 'page_path' );

		if ( $pageName !== false ) {
			$pagePath = str_replace( '$1', rawurlencode( $pageName ), $pagePath );
		}

		return $pagePath;
	}

	/**
	 * Returns $pageName without changes.
	 * Subclasses may override this to apply some kind of normalization.
	 *
	 * @see Site::normalizePageName
	 *
	 * @since 1.20
	 *
	 * @param string $pageName
	 *
	 * @return string
	 */
	public function normalizePageName( $pageName ) {
		return $pageName;
	}

	/**
	 * @see Site::getExtraData
	 *
	 * @since 1.20
	 *
	 * @return array
	 */
	public function getExtraData() {
		return $this->getField( 'data' );
	}

	/**
	 * @see Site::getLanguage
	 *
	 * @since 1.20
	 *
	 * @return string|bool
	 */
	public function getLanguageCode() {
		return $this->getField( 'language', false );
	}

	/**
	 * Returns the local identifiers of this site.
	 *
	 * @since 1.20
	 *
	 * @param string $type
	 *
	 * @return array
	 */
	protected function getLocalIds( $type ) {
		if ( $this->localIds === false ) {
			$this->loadLocalIds();
		}

		return array_key_exists( $type, $this->localIds ) ? $this->localIds[$type] : array();
	}

	/**
	 * Loads the local ids for the site.
	 *
	 * @since 1.20
	 */
	protected function loadLocalIds() {
		$dbr = wfGetDB( $this->getTable()->getReadDb() );

		$ids = $dbr->select(
			'site_identifiers',
			array(
				'si_type',
				'si_key',
			),
			array(
				'si_site' => $this->getId(),
			),
			__METHOD__
		);

		$this->localIds = array();

		foreach ( $ids as $id ) {
			if ( !array_key_exists( $id->si_type, $this->localIds ) ) {
				$this->localIds[$id->si_type] = array();
			}

			$this->localIds[$id->si_type][] = $id->si_key;
		}
	}

	/**
	 * @see Site::getInterwikiIds
	 *
	 * @since 1.20
	 *
	 * @return array of string
	 */
	public function getInterwikiIds() {
		return $this->getLocalIds( 'interwiki' );
	}

	/**
	 * @see Site::getNavigationIds
	 *
	 * @since 1.20
	 *
	 * @return array of string
	 */
	public function getNavigationIds() {
		return $this->getLocalIds( 'equivalent' );
	}

	/**
	 * Compatibility helper.
	 * Can be used by code that still needs the old Interwiki class,
	 * but this should be updated, as this method will eventually be removed.
	 *
	 * @since 1.20
	 * @deprecated since 0.1
	 *
	 * @return \Interwiki
	 */
//	public function toInterwiki() {
//		return new \Interwiki(
//			$this->getConfig()->getLocalId(),
//			$this->getUrl(),
//			$this->getFilePath( 'api.php' ),
//			'',
//			$this->getConfig()->getForward(),
//			$this->getConfig()->getAllowTransclusion()
//		);
//	}

}