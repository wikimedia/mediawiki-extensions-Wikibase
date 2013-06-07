<?php

namespace Wikibase;

use Diff\Patcher;
use InvalidArgumentException;
use OutOfBoundsException;
use Wikibase\DataModel\SimpleSiteLink;

/**
 * Represents a single Wikibase item.
 * See https://meta.wikimedia.org/wiki/Wikidata/Data_model#Items
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
 * @ingroup WikibaseDataModel
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class Item extends Entity {

	const ENTITY_TYPE = 'item';

	/**
	 * @since 0.2
	 *
	 * @var Claims|null
	 */
	protected $statements = null;

	/**
	 * Adds a site link.
	 *
	 * @since 0.1
	 * @deprecated since 0.4, use addSimpleSiteLink instead
	 *
	 * @param SiteLink $link the link to the target page
	 * @param string $updateType
	 *
	 * @return SiteLink|bool Returns the link on success, or false on failure
	 */
	public function addSiteLink( SiteLink $link, $updateType = 'add' ) {
		$siteId = $link->getSite()->getGlobalId();

		$success =
			( $updateType === 'add' && !array_key_exists( $siteId, $this->data['links'] ) )
				|| ( $updateType === 'update' && array_key_exists( $siteId, $this->data['links'] ) )
				|| ( $updateType === 'set' );

		if ( $success ) {
			$this->data['links'][$siteId] = $link->getPage();
		}

		return $success ? $link : false;
	}

	/**
	 * Adds a site link to the list of site links.
	 * If there already is a site link with the site id of the provided site link,
	 * then that one will be overridden by the provided one.
	 *
	 * @since 0.4
	 *
	 * @param SimpleSiteLink $siteLink
	 */
	public function addSimpleSiteLink( SimpleSiteLink $siteLink ) {
		$this->data['links'][$siteLink->getSiteId()] = $siteLink->getPageName();
	}

	/**
	 * Removes the sitelink with specified site ID if the Item has such a sitelink.
	 * A page name can be provided to have removal only happen when it matches what is set.
	 * A boolean is returned indicating if a link got removed or not.
	 *
	 * @since 0.1
	 *
	 * @param string $siteId the target site's id
	 * @param bool|string $pageName he target page's name (in normalized form)
	 *
	 * @return bool Success indicator
	 */
	public function removeSiteLink( $siteId, $pageName = false ) {
		if ( $pageName !== false ) {
			$success = array_key_exists( $siteId, $this->data['links'] ) && $this->data['links'][$siteId] === $pageName;
		}
		else {
			$success = array_key_exists( $siteId, $this->data['links'] );
		}

		if ( $success ) {
			unset( $this->data['links'][$siteId] );
		}

		return $success;
	}

	/**
	 * Returns the site links in an associative array with the following format:
	 * site id (str) => SiteLink
	 *
	 * @since 0.1
	 * @deprecated since 0.4, use getSimpleSiteLinks instead
	 *
	 * @return array a list of SiteLink objects
	 */
	public function getSiteLinks() {
		$links = array();

		foreach ( $this->data['links'] as $globalSiteId => $title ) {
			$links[] = SiteLink::newFromText( $globalSiteId, $title );
		}

		return $links;
	}

	/**
	 * @since 0.4
	 *
	 * @return SimpleSiteLink[]
	 */
	public function getSimpleSiteLinks() {
		$links = array();

		foreach ( $this->data['links'] as $siteId => $pageName ) {
			$links[] = new SimpleSiteLink( $siteId, $pageName );
		}

		return $links;
	}

	/**
	 * @since 0.4
	 *
	 * @param string $siteId
	 *
	 * @return SimpleSiteLink
	 * @throws OutOfBoundsException
	 */
	public function getSimpleSiteLink( $siteId ) {
		if ( !array_key_exists( $siteId, $this->data['links'] ) ) {
			throw new OutOfBoundsException( "There is no site link with site id '$siteId'" );
		}

		return new SimpleSiteLink( $siteId, $this->data['links'][$siteId] );
	}

	/**
	 * @since 0.4
	 *
	 * @param string $siteId
	 *
	 * @return bool
	 */
	public function hasLinkToSite( $siteId ) {
		return array_key_exists( $siteId, $this->data['links'] );
	}

	/**
	 * @see Entity::isEmpty
	 *
	 * @since 0.1
	 *
	 * @return boolean
	 */
	public function isEmpty() {
		return parent::isEmpty()
			&& $this->data['links'] === array();
	}

	/**
	 * @see Entity::cleanStructure
	 *
	 * @since 0.1
	 *
	 * @param boolean $wipeExisting
	 */
	protected function cleanStructure( $wipeExisting = false ) {
		parent::cleanStructure( $wipeExisting );

		foreach ( array( 'links' ) as $field ) {
			if (  $wipeExisting || !array_key_exists( $field, $this->data ) ) {
				$this->data[$field] = array();
			}
		}
	}

	/**
	 * @see Entity::newFromArray
	 *
	 * @since 0.1
	 *
	 * @param array $data
	 *
	 * @return Item
	 */
	public static function newFromArray( array $data ) {
		return new static( $data );
	}

	/**
	 * @since 0.1
	 *
	 * @return Item
	 */
	public static function newEmpty() {
		return self::newFromArray( array() );
	}

	/**
	 * @see Entity::getType
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getType() {
		return Item::ENTITY_TYPE;
	}

	/**
	 * @see Entity::newClaimBase
	 *
	 * @since 0.3
	 *
	 * @param Snak $mainSnak
	 *
	 * @return Statement
	 */
	protected function newClaimBase( Snak $mainSnak ) {
		return new Statement( $mainSnak );
	}

	/**
	 * @see Entity::entityToDiffArray
	 *
	 * @since 0.4
	 *
	 * @param Entity $entity
	 *
	 * @return array
	 * @throws InvalidArgumentException
	 */
	protected function entityToDiffArray( Entity $entity ) {
		if ( !( $entity instanceof Item ) ) {
			throw new InvalidArgumentException( 'ItemDiffer only accepts Item objects' );
		}

		$array = parent::entityToDiffArray( $entity );

		$array['links'] = array();

		foreach ( $entity->getSimpleSiteLinks() as $siteLink ) {
			$array['links'][$siteLink->getSiteId()] = $siteLink->getPageName();
		}

		return $array;
	}

	/**
	 * @see Entity::patchSpecificFields
	 *
	 * @since 0.4
	 *
	 * @param EntityDiff $patch
	 * @param Patcher $patcher
	 */
	protected function patchSpecificFields( EntityDiff $patch, Patcher $patcher ) {
		if ( $patch instanceof ItemDiff ) {
			$siteLinksDiff = $patch->getSiteLinkDiff();

			if ( !$siteLinksDiff->isEmpty() ) {
				$links = $this->data['links'];
				$links = $patcher->patch( $links, $siteLinksDiff );
				$this->data['links'] = $links;
			}
		}
	}

}

// Compatibility with 0.3 and earlier.
class ItemObject extends Item {}
