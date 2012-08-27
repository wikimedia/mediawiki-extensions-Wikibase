<?php

/**
 * Implementation of SiteList using GenericArrayObject.
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
 */
class SiteArray extends GenericArrayObject implements SiteList {

	/**
	 * Internal site identifiers pointing to their sites offset value.
	 *
	 * @since 1.20
	 *
	 * @var array of integer
	 */
	protected $byInternalId = array();

	/**
	 * Global site identifiers pointing to their sites offset value.
	 *
	 * @since 1.20
	 *
	 * @var array of string
	 */
	protected $byGlobalId = array();

	/**
	 * @see GenericArrayObject::getObjectType
	 *
	 * @since 1.20
	 *
	 * @return string
	 */
	public function getObjectType() {
		return 'Site';
	}

	/**
	 * @see GenericArrayObject::preSetElement
	 *
	 * @since 0.1
	 *
	 * @param int|string $index
	 * @param Site $site
	 *
	 * @return boolean
	 */
	protected function preSetElement( $index, $site ) {
		if ( $this->hasGlobalId( $site->getGlobalId() ) ) {
			$this->removeSiteByGlobalId( $site->getGlobalId() );
		}

		$this->byGlobalId[$site->getGlobalId()] = $index;
		$this->byInternalId[$site->getId()] = $index;
	}

	/**
	 * @see ArrayObject::offsetUnset()
	 *
	 * @since 1.20
	 *
	 * @param mixed $index
	 */
	public function offsetUnset( $index ) {
		/**
		 * @var Site $site
		 */
		$site = $this->offsetGet( $index );

		if ( $site !== false ) {
			unset( $this->byGlobalId[$site->getGlobalId()] );
			unset( $this->byInternalId[$site->getId()] );
		}

		parent::offsetUnset( $index );
	}

	/**
	 * @see SiteList::getGlobalIdentifiers
	 *
	 * @since 1.20
	 *
	 * @return array
	 */
	public function getGlobalIdentifiers() {
		return array_keys( $this->byGlobalId );
	}

	/**
	 * @see SiteList::hasGlobalId
	 *
	 * @param string $globalSiteId
	 *
	 * @return boolean
	 */
	public function hasGlobalId( $globalSiteId ) {
		return array_key_exists( $globalSiteId, $this->byGlobalId );
	}

	/**
	 * @see SiteList::getSiteByGlobalId
	 *
	 * @since 1.20
	 *
	 * @param string $globalSiteId
	 *
	 * @return Site
	 */
	public function getSiteByGlobalId( $globalSiteId ) {
		return $this->offsetGet( $this->byGlobalId[$globalSiteId] );
	}

	/**
	 * @see SiteList::removeSiteByGlobalId
	 *
	 * @since 1.20
	 *
	 * @param string $globalSiteId
	 */
	public function removeSiteByGlobalId( $globalSiteId ) {
		$this->offsetUnset( $this->byGlobalId[$globalSiteId] );
	}

	/**
	 * @see SiteList::isEmpty
	 *
	 * @since 1.20
	 *
	 * @return boolean
	 */
	public function isEmpty() {
		return $this->byGlobalId === array();
	}

	/**
	 * @see SiteList::has
	 *
	 * @param integer $id
	 *
	 * @return boolean
	 */
	public function has( $id ) {
		return array_key_exists( $id, $this->byInternalId );
	}

	/**
	 * @see SiteList::getSite
	 *
	 * @since 1.20
	 *
	 * @param integer $id
	 *
	 * @return Site
	 */
	public function getSite( $id ) {
		return $this->offsetGet( $this->byInternalId[$id] );
	}

	/**
	 * @see SiteList::removeSite
	 *
	 * @since 1.20
	 *
	 * @param integer $id
	 */
	public function removeSite( $id ) {
		$this->offsetUnset( $this->byInternalId[$id] );
	}

	/**
	 * @see SiteList::setSite
	 *
	 * @since 1.20
	 *
	 * @param Site $site
	 */
	public function setSite( Site $site ) {
		$this[] = $site;
	}

}