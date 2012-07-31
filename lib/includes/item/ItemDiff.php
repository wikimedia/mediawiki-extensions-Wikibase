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
	 * @static
	 * @param $baseLinks
	 * @return array
	 */
	public static function siteLinksToArray( $baseLinks ) {
		$links = array();

		/* @var SiteLink $link */
		foreach ( $baseLinks as $link ) {
			$links[ $link->getSiteID() ] = $link->getPage();
		}

		return $links;
	}

	/**
	 * @static
	 * @param Item $oldItem
	 * @param Item $newItem
	 * @return EntityDiff
	 */
	public static function newFromItems( Item $oldItem, Item $newItem ) {
		return parent::newFromEntities( $oldItem, $newItem, array(
			'links' => MapDiff::newFromArrays(
				self::siteLinksToArray( $oldItem->getSiteLinks() ),
				self::siteLinksToArray( $newItem->getSiteLinks() )
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
	 * Loops over diffs for links
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
	 * @param $site
	 * @param \Diff\DiffOp $diffOp
	 * @param \Wikibase\Item $item
	 * @return bool
	 * @throws \MWException
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
