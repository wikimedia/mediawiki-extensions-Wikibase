<?php

use MWException;

/**
 * A list of Site objects.
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
 * @ingroup Sites
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SiteList extends GenericArrayObject {

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
	 * Global site identifiers pointing to their sites offset value.
	 * @since 1.20
	 * @var array
	 */
	protected $byGlobalId = array();

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
			unset( $this->byGlobalId[$site->get] );
		}

		parent::offsetUnset( $index );
	}

	/**
	 * Returns all the global site identifiers.
	 * Optionally only those belonging to the specified group.
	 *
	 * @since 1.20
	 *
	 * @return array
	 */
	public function getGlobalIdentifiers() {
		return array_keys( $this->byGlobalId );
	}

	/**
	 * Returns if the list contains the site with the provided global site identifier.
	 *
	 * @param string $globalSiteId
	 *
	 * @return boolean
	 */
	public function hasGlobalId( $globalSiteId ) {
		return array_key_exists( $globalSiteId, $this->byGlobalId );
	}

	/**
	 * Returns the Site with the provided global site id.
	 * The site needs to exist, so if not sure, call hasGlobalId first.
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
	 * Removes the site with the specified global id.
	 * The site needs to exist, so if not sure, call hasGlobalId first.
	 *
	 * @since 1.20
	 *
	 * @param string $globalSiteId
	 */
	public function removeSiteByGlobalId( $globalSiteId ) {
		$this->offsetUnset( $this->byGlobalId[$globalSiteId] );
	}

	/**
	 * Returns if the site list contains no sites.
	 *
	 * @since 1.20
	 *
	 * @return boolean
	 */
	public function isEmpty() {
		return $this->count() === 0;
	}

}