<?php

/**
 * Class representing a single site.
 *
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
 * @ingroup Site
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Werner
 */
class SiteObject extends ORMRow implements Site {

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
	 * @see Site::setGlobalId
	 *
	 * @since 1.20
	 *
	 * @param string $globalId
	 */
	public function setGlobalId( $globalId ) {
		$this->setField( 'global_key', $globalId );
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
	 * @see Site::setType
	 *
	 * @since 1.20
	 *
	 * @param string $type
	 */
	public function setType( $type ) {
		$this->setField( 'type', $type );
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
	 * @see Site::setGroup
	 *
	 * @since 1.20
	 *
	 * @param string $group
	 */
	public function setGroup( $group ) {
		$this->setField( 'group', $group );
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
	 * @see Site::setSource
	 *
	 * @since 1.20
	 *
	 * @param string $source
	 */
	public function setSource( $source ) {
		$this->setField( 'source', $source );
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
	 * Returns the value of a type specific field, or the value
	 * of the $default parameter in case it's not set.
	 *
	 * @since 1.20
	 *
	 * @param string $fieldName
	 * @param mixed $default
	 *
	 * @return array
	 */
	protected function getExtraData( $fieldName, $default = null ) {
		$data = $this->getField( 'data' );
		return array_key_exists( $fieldName,$data ) ? $data[$fieldName] : $default;
	}

	/**
	 * Sets the value of a type specific field.
	 * @since 1.20
	 *
	 * @param string $fieldName
	 * @param mixed $value
	 */
	protected function setExtraData( $fieldName, $value = null ) {
		$data = $this->getField( 'data' );
		$data[$fieldName] = $value;
		$this->setField( 'data', $data );
	}

	/**
	 * @see Site::getLanguageCode
	 *
	 * @since 1.20
	 *
	 * @return string
	 */
	public function getLanguageCode() {
		return $this->getField( 'language' );
	}

	/**
	 * @see Site::setLanguageCode
	 *
	 * @since 1.20
	 *
	 * @param string $languageCode
	 */
	public function setLanguageCode( $languageCode ) {
		$this->setField( 'language', $languageCode );
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
			$this->addLocalId( $id->si_type, $id->si_key );
		}
	}

	/**
	 * Adds a local identifier.
	 *
	 * @since 1.20
	 *
	 * @param string $type
	 * @param string $identifier
	 */
	public function addLocalId( $type, $identifier ) {
		if ( !array_key_exists( $type, $this->localIds ) ) {
			$this->localIds[$type] = array();
		}

		if ( !in_array( $identifier, $this->localIds[$type] ) ) {
			$this->localIds[$type][] = $identifier;
		}
	}

	/**
	 * @see Site::addInterwikiId
	 *
	 * @since 1.20
	 *
	 * @param string $identifier
	 */
	public function addInterwikiId( $identifier ) {
		$this->addLocalId( 'interwiki', $identifier );
	}

	/**
	 * @see Site::addNavigationId
	 *
	 * @since 1.20
	 *
	 * @param string $identifier
	 */
	public function addNavigationId( $identifier ) {
		$this->addLocalId( 'equivalent', $identifier );
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
	 * @see Site::getInternalId
	 *
	 * @since 1.20
	 *
	 * @return integer
	 */
	public function getInternalId() {
		return $this->getId();
	}

	/**
	 * @see Site::setInternalId
	 *
	 * @since 1.20
	 *
	 * @param integer $id
	 */
	public function setInternalId( $id ) {
		$this->setId( $id );
	}

}