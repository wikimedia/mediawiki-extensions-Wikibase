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

	protected static function siteLinksToArray( $baseLinks ) {
		$links = array();

		/* @var SiteLink $link */
		foreach ( $baseLinks as $link ) {
			$links[ $link->getSiteID() ] = $link->getPage();
		}

		return $links;
	}

	public static function newFromItems( Item $oldItem, Item $newItem ) {
		return static::newFromEntities( $oldItem, $newItem, array(
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
		return $this['links'];
	}

	public function apply( Entity $entity ) {
		return ( $this->applyLinks( $this->getSiteLinkDiff(), $entity )
		        && parent::apply( $entity ) );
	}

	private function applyLinks( MapDiff $linkOps, Entity $entity ) {
		foreach ( $linkOps as $site => $ops ) {
			foreach ( $ops as $op ) {
				$this->applyLink( $site, $op, $entity );
			}
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
			$link = SiteLink::newFromText( $site, $diffOp->getNewValue() );
			$item->removeSiteLink( $link, $site );
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
