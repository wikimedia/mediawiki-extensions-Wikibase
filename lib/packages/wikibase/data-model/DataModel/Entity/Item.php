<?php

namespace Wikibase;

use Diff\Patcher;
use InvalidArgumentException;
use OutOfBoundsException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SimpleSiteLink;

/**
 * Represents a single Wikibase item.
 * See https://meta.wikimedia.org/wiki/Wikidata/Data_model#Items
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseDataModel
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Michał Łazowik
 */
class Item extends Entity {

	const ENTITY_TYPE = 'item';

	/**
	 * @since 0.5
	 *
	 * @var SimpleSiteLink[]|null
	 */
	protected $siteLinks = null;

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
		$this->unstubSiteLinks();
		$this->siteLinks[ $siteLink->getSiteId() ] = $siteLink;
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
		$this->unstubSiteLinks();

		if ( $pageName !== false ) {
			$success = array_key_exists( $siteId, $this->siteLinks ) && $this->siteLinks[ $siteId ]->getPageName() === $pageName;
		}
		else {
			$success = array_key_exists( $siteId, $this->siteLinks );
		}

		if ( $success ) {
			unset( $this->siteLinks[ $siteId ] );
		}

		return $success;
	}

	/**
	 * @since 0.4
	 *
	 * @return SimpleSiteLink[]
	 */
	public function getSimpleSiteLinks() {
		$this->unstubSiteLinks();

		$links = array();

		foreach ( $this->siteLinks as $link ) {
			$links[] = $link;
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
		$this->unstubSiteLinks();

		if ( !array_key_exists( $siteId, $this->siteLinks ) ) {
			throw new OutOfBoundsException( "There is no site link with site id $siteId" );
		}

		return $this->siteLinks[ $siteId ];
	}

	/**
	 * @since 0.4
	 *
	 * @param string $siteId
	 *
	 * @return bool
	 */
	public function hasLinkToSite( $siteId ) {
		$this->unstubSiteLinks();
		return array_key_exists( $siteId, $this->siteLinks );
	}

	/**
	 * Unstubs sitelinks from the unserialized data.
	 *
	 * @since 0.5
	 */
	protected function unstubSiteLinks() {
		if ( $this->siteLinks === null ) {
			$this->siteLinks = array();

			foreach ( $this->data['links'] as $siteId => $linkSerialization ) {
				$this->siteLinks[$siteId] = SimpleSiteLink::newFromArray( $siteId, $linkSerialization );
			}
		}
	}

	/**
	 * Returns the SimpleSiteLinks as stubs.
	 *
	 * @since 0.5
	 *
	 * @return array
	 */
	protected function getStubbedSiteLinks() {
		if ( is_string( reset( $this->data['links'] ) ) ) {
			// legacy serialization
			$this->unstubSiteLinks();
		}

		if ( $this->siteLinks !== null ) {
			$siteLinks = array();

			foreach ( $this->siteLinks as $siteId => $siteLink ) {
				$siteLinks[$siteId] = $siteLink->toArray();
			}
		} else {
			$siteLinks = $this->data['links'];
		}

		return $siteLinks;
	}

	/**
	 * @since 0.5
	 *
	 * @return bool
	 */
	 public function hasSiteLinks() {
		if ( $this->siteLinks === null ) {
			return $this->data['links'] !== array();
		} else {
			return !empty( $this->siteLinks );
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
			&& !$this->hasSiteLinks();
	}

	/**
	 * @see Entity::stub
	 *
	 * @since 0.5
	 */
	public function stub() {
		parent::stub();
		$this->data['links'] = $this->getStubbedSiteLinks();
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

		$this->siteLinks = null;
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

		$array['links'] = $entity->getStubbedSiteLinks();

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
				$links = $this->getStubbedSiteLinks();
				$links = $patcher->patch( $links, $siteLinksDiff );

				$this->siteLinks = array();
				foreach ( $links as $siteId => $linkSerialization ) {
					if ( array_key_exists( 'name', $linkSerialization ) ) {
						$this->siteLinks[$siteId] = SimpleSiteLink::newFromArray( $siteId, $linkSerialization );
					}
				}
			}
		}
	}

	/**
	 * @since 0.5
	 *
	 * @param string $idSerialization
	 *
	 * @return EntityId
	 */
	protected function idFromSerialization( $idSerialization ) {
		return new ItemId( $idSerialization );
	}

}
