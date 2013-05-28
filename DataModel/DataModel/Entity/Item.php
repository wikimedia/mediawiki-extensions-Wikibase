<?php

namespace Wikibase;

use Diff\Patcher;
use MWException;

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
 * @author Daniel Kinzler
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
		if ( $pageName !== false) {
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
	 * Replaces the currently set sitelinks with the provided ones.
	 *
	 * @since 0.4
	 *
	 * @param SiteLink[] $siteLinks
	 */
	public function setSiteLinks( array $siteLinks ) {
		$this->data['links'] = array();

		foreach ( $siteLinks as $siteLink ) {
			$this->data['links'][$siteLink->getSite()->getGlobalId()] = $siteLink->getPage();
		}
	}

	/**
	 * Returns the site links in an associative array with the following format:
	 * site id (str) => SiteLink
	 *
	 * @since 0.4
	 *
	 * @param string|null $group Gtroup to get links for (if not given, all links are returned)
	 *
	 * @return SiteLink[]
	 */
	public function getSiteLinks( $group = null ) {
		wfProfileIn( __METHOD__ );

		$links = array();

		foreach ( $this->data['links'] as $globalSiteId => $title ) {
			//TODO: get rid of global state here!
			$link = SiteLink::newFromText( $globalSiteId, $title );

			if ( $group !== null && $link->getSite()->getGroup() !== $group ) {
				continue;
			}

			$links[] = $link;
		}

		wfProfileOut( __METHOD__ );
		return $links;
	}

	/**
	 * Returns the site link for the given site id, or null.
	 *
	 * @since 0.1
	 *
	 * @param String $siteId the id of the site to which to get the lin
	 *
	 * @return SiteLink|null the corresponding SiteLink object, or null
	 */
	public function getSiteLink( $siteId ) {
		if ( array_key_exists( $siteId, $this->data['links'] ) ) {
			return SiteLink::newFromText( $siteId, $this->data['links'][$siteId] );
		} else {
			return null;
		}
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
	 * @throws MWException
	 */
	protected function entityToDiffArray( Entity $entity ) {
		if ( !( $entity instanceof Item ) ) {
			throw new MWException( 'ItemDiffer only accepts Item objects' );
		}

		$array = parent::entityToDiffArray( $entity );

		$array['links'] = SiteLink::siteLinksToArray( $entity->getSiteLinks() );

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
