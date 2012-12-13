<?php

namespace Wikibase;
use Diff\MapDiff;
use Diff\IDiffOp;

/**
 * EntityDiff generator.
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
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Jens Ohlig
 */
class EntityDiffer {

	/**
	 * Creates a new Entity diff from two entities.
	 *
	 * @since 0.4
	 *
	 * @param Entity $oldEntity
	 * @param Entity $newEntity
	 * @param IDiffOp[] $diffOps
	 *
	 * @return EntityDiff
	 */
	protected function newDiffFromEntities( Entity $oldEntity, Entity $newEntity, array $diffOps ) {
		return new MapDiff( array_merge( array(
			'aliases' => MapDiff::newFromArrays(
				$oldEntity->getAllAliases(),
				$newEntity->getAllAliases(),
				true
			),
			'label' => MapDiff::newFromArrays(
				$oldEntity->getLabels(),
				$newEntity->getLabels()
			),
			'description' => MapDiff::newFromArrays(
				$oldEntity->getDescriptions(),
				$newEntity->getDescriptions()
			)
		), $diffOps ) );
	}

	/**
	 * Applies diff operations to an entity.
	 *
	 * @since 0.4
	 *
	 * @param EntityDiff $patch
	 * @param Entity $entity
	 */
	public function apply( EntityDiff $patch, Entity $entity ) {
		$this->applyAliases( $patch->getAliasesDiff(), $entity );
		$this->applyLabels( $patch->getLabelsDiff(), $entity );
		$this->applyDescriptions( $patch->getDescriptionsDiff(), $entity );
	}

	/**
	 * Applies changes to the alias part of the entity
	 *
	 * @since 0.4
	 *
	 * @param MapDiff $aliasesOps
	 * @param Entity $entity
	 */
	private function applyAliases( MapDiff $aliasesOps, Entity $entity ) {


		foreach ( $aliasesOps as $lang => $ops ) {
			foreach ( $ops as $op ) {
				$this->applyAlias( $lang, $op, $entity );
			}
		}
	}

	/**
	 * Apply a single DiffOp for an alias
	 *
	 * @since 0.4
	 *
	 * @param string $lang the key to perform the operation on
	 * @param IDiffOp $diffOp the operation to perform
	 * @param Entity $entity the entity to modify
	 *
	 * @throws \MWException the the type of the DiffOp is unknown
	 *
	 * @return bool true
	 */
	private function applyAlias( $lang, IDiffOp $diffOp, Entity $entity ) {
		$type = $diffOp->getType();
		if ( $type === "add" ) {
			$entity->addAliases( $lang, array( $diffOp->getNewValue() ) );
		} elseif ( $type === "remove" ) {
			$entity->removeAliases( $lang, array ( $diffOp->getOldValue() ) );
		} elseif ( $type === "change" ) {
			$entity->removeAliases( $lang, array ( $diffOp->getOldValue() ) );
			$entity->addAliases( $lang, array( $diffOp->getNewValue() ) );
		} else {
			throw new \MWException( "Unsupported operation: $type" );
		}
		return true;
	}

	/**
	 * Applies changes to the labels part of the entity
	 *
	 * @since 0.4
	 *
	 * @param \Diff\MapDiff $labelOps
	 * @param Entity $entity
	 */
	private function applyLabels( MapDiff $labelOps, Entity $entity ) {
		foreach ( $labelOps as $lang => $op ) {
			$this->applyLabel( $lang, $op, $entity );
		}
	}

	/**
	 * Apply a single DiffOp for a label
	 *
	 * @since 0.4
	 *
	 * @param String     $lang   the key to perform the operation on
	 * @param DiffOp     $diffOp the operation to perform
	 * @param Entity     $entity the entity to modify
	 *
	 * @throws \MWException the the type of the DiffOp is unknown
	 *
	 * @return bool true
	 */
	private function applyLabel( $lang, \Diff\DiffOp $diffOp, Entity $entity ) {
		$type = $diffOp->getType();
		if ( $type === "add" ) {
			$entity->setLabel( $lang, $diffOp->getNewValue() );
		} elseif ( $type === "remove" ) {
			$entity->removeLabel( array( $lang ) );
		} elseif ( $type === "change" ) {
			$entity->setLabel( $lang, $diffOp->getNewValue() );
		} else {
			throw new \MWException( "Unsupported operation: $type" );
		}
		return true;
	}

	/**
	 * Applies changes to the description part of the entity
	 *
	 * @since 0.4
	 *
	 * @param \Diff\MapDiff $descriptionOps
	 * @param Entity $entity
	 */
	private function applyDescriptions( MapDiff $descriptionOps, Entity $entity ) {
		foreach ( $descriptionOps as $lang => $op ) {
			$this->applyDescription( $lang, $op, $entity );
		}
	}

	/**
	 * Apply a single DiffOp for a description
	 *
	 * @since 0.4
	 *
	 * @param String     $lang   the key to perform the operation on
	 * @param DiffOp     $diffOp the operation to perform
	 * @param Entity     $entity the entity to modify
	 *
	 * @throws \MWException the the type of the DiffOp is unknown
	 *
	 * @return bool true
	 */
	private function applyDescription( $lang, \Diff\DiffOp $diffOp, Entity $entity ) {
		$type = $diffOp->getType();
		if ( $type === "add" ) {
			$entity->setDescription( $lang, $diffOp->getNewValue()  );
		} elseif ( $type === "remove" ) {
			$entity->removeDescription( array( $lang ) );
		} elseif ( $type === "change" ) {
			$entity->setDescription( $lang, $diffOp->getNewValue() );
		} else {
			throw new \MWException( "Unsupported operation: $type" );
		}
		return true;
	}

	/**
	 * Create a new EntityDiff from a MapDiff
	 * (which must hold all the top level fields).
	 *
	 * @since 0.4
	 *
	 * @param MapDiff $mapDiff
	 *
	 * @return EntityDiff
	 */
	protected static function newFromMapDiff( MapDiff $mapDiff ) {
		return new static( iterator_to_array( $mapDiff ) );
	}

	/**
	 * @see IDiff::getApplicableDiff
	 *
	 * @since 0.4
	 *
	 * @param array $currentObject
	 *
	 * @return EntityDiff
	 */
	public function getApplicableDiff( array $currentObject ) {
		return static::newFromMapDiff( parent::getApplicableDiff( $currentObject ) );
	}









	/////////////////////








	/**
	 * Creates a new ItemDiff representing the difference between $oldItem and $newItem
	 *
	 * @since 0.4
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
	 * Applies diff for links
	 *
	 * @since 0.4
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
	 * @since 0.4
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

}
