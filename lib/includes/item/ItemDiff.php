<?php

namespace Wikibase;
use Diff\MapDiff as MapDiff;

/**
 * Represents a diff between two WikibaseItem instances.
 * Acts as a container for diffs between the various fields
 * of the items. Also contains methods to obtain these
 * diffs as Wikibase\Change objects.
 *
 * Immutable.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Jens Ohlig
 */
class ItemDiff extends EntityDiffObject {

	/**
	 * Creates a new ItemDiff representing the difference between $oldItem and $newItem
	 *
	 * @since 0.1
	 *
	 * @param Item $oldItem
	 * @param Item $newItem
	 * @return EntityDiff
	 */
	public static function newFromItems( Item $oldItem, Item $newItem ) {
		return parent::newFromEntities( $oldItem, $newItem, array(
			'links' => MapDiff::newFromArrays(
				SiteLink::siteLinksToArray( $oldItem->getSiteLinks() ),
				SiteLink::siteLinksToArray( $newItem->getSiteLinks() )
			)
		) );
	}

	/**
	 * Returns a MapDiff object with the sitelink differences.
	 *
	 * @since 0.1
	 *
	 * @return MapDiff
	 */
	public function getSiteLinkDiff() {
		return isset( $this['links'] ) ? $this['links'] : new \Diff\MapDiff( array() );
	}

	/**
	 * Applies diff for links
	 *
	 * @since 0.1
	 *
	 * @param Entity $entity
	 */
	public function apply( Entity $entity ) {
		$this->applyLinks( $this->getSiteLinkDiff(), $entity );
		parent::apply( $entity );
	}

	/**
	 * Applies changes to the sitelink part of the entity
	 *
	 * @since 0.1
	 *
	 * @param \Diff\MapDiff $linkOps
	 * @param Entity $entity
	 */
	private function applyLinks( MapDiff $linkOps, Entity $entity ) {
		foreach ( $linkOps as $site => $op ) {
			$this->applyLink( $site, $op, $entity );
		}
	}

	/**
	 * Applies a single DiffOp for a sitelink
	 *
	 * @param String           $site   the key to perform the operation on
	 * @param \Diff\DiffOp     $diffOp the operation to perform
	 * @param Entity           $item   the entity to modify
	 *
	 * @throws \MWException the the type of the DiffOp is unknown
	 *
	 * @return bool true
	 */
	private function applyLink( $site, \Diff\DiffOp $diffOp, Entity $item ) {
		$type = $diffOp->getType();
		if ( $type === "add" ) {
			$link = SiteLink::newFromText( $site, $diffOp->getNewValue() );
			$item->addSiteLink( $link, "add" );
		} elseif ( $type === "remove" ) {
			$item->removeSiteLink( $site, $diffOp->getOldValue() );
		} elseif ( $type === "change" ) {
			$link = SiteLink::newFromText( $site, $diffOp->getNewValue() );
			$item->addSiteLink( $link, "update" );
		} else {
			throw new \MWException( "Unsupported operation: $type" );
		}
		return true;
	}

	/**
	 * @see EntityDiff::isEmpty
	 *
	 * @since 0.1
	 *
	 * @return boolean
	 */
	public function isEmpty() {
		return parent::isEmpty() && $this->getSiteLinkDiff()->isEmpty();
	}

	/**
	 * @see EntityDiff::getView
	 *
	 * @since 0.1
	 *
	 * @return ItemDiffView
	 */
	public function getView() {
		return new ItemDiffView( array(), $this );
	}



}
