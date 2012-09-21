<?php

namespace Wikibase;
use Diff\MapDiff as MapDiff;
use Diff\DiffOp;
use Diff\IDiff as IDiff;


/**
 * Represents a diff between two Wikibase\Entity instances.
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
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Jens Ohlig
 */
abstract class EntityDiffObject extends MapDiff implements EntityDiff {

	/**
	 * Creates a new Entity diff from two entities.
	 *
	 * @since 0.1
	 *
	 * @param Entity $oldEntity
	 * @param Entity $newEntity
	 * @param array $diffOps
	 *
	 * @return EntityDiff
	 */
	protected static function newFromEntities( Entity $oldEntity, Entity $newEntity, array $diffOps ) {
		return new static( array_merge( array(
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
	 * @since 0.1
	 *
	 * @param Entity $entity
	 * @return Boolean
	 */
	public function apply( Entity $entity ) {
		$this->applyAliases( $this->getAliasesDiff(), $entity );
		$this->applyLabels( $this->getLabelsDiff(), $entity );
		$this->applyDescriptions( $this->getDescriptionsDiff(), $entity );
		return true;
	}

	/**
	 * Applies changes to the alias part of the entity
	 *
	 * @since 0.1
	 *
	 * @param \Diff\MapDiff $aliasesOps
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
	 * @since 0.1
	 *
	 * @param String     $lang   the key to perform the operation on
	 * @param DiffOp     $diffOp the operation to perform
	 * @param Entity     $entity the entity to modify
	 *
	 * @throws \MWException the the type of the DiffOp is unknown
	 *
	 * @return bool true
	 */
	private function applyAlias( $lang, DiffOp $diffOp, Entity $entity ) {
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
	 * @since 0.1
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
	 * @since 0.1
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
	 * @since 0.1
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
	 * @since 0.1
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
	 * Returns a MapDiff object with the aliases differences.
	 *
	 * @since 0.1
	 *
	 * @return MapDiff
	 */
	public function getAliasesDiff() {
		return isset( $this['aliases'] ) ? $this['aliases'] : new \Diff\MapDiff( array() );
	}

	/**
	 * Returns a MapDiff object with the labels differences.
	 *
	 * @since 0.1
	 *
	 * @return MapDiff
	 */
	public function getLabelsDiff() {
		return isset( $this['label'] ) ? $this['label'] : new \Diff\MapDiff( array() );
	}

	/**
	 * Returns a MapDiff object with the descriptions differences.
	 *
	 * @since 0.1
	 *
	 * @return MapDiff
	 */
	public function getDescriptionsDiff() {
		return isset( $this['description'] ) ? $this['description'] : new \Diff\MapDiff( array() );
	}

	/**
	 * Returns if there are any changes (equivalent to: any differences between the entities).
	 *
	 * @since 0.1
	 *
	 * @return boolean
	 */
	public function isEmpty() {
		return $this->getDescriptionsDiff()->isEmpty()
			&& $this->getAliasesDiff()->isEmpty()
			&& $this->getLabelsDiff()->isEmpty();
	}

	/**
	 * Create a new EntityDiff from a MapDiff
	 * (which must hold all the top level fields).
	 *
	 * @since 0.1
	 *
	 * @param MapDiff $mapDiff
	 *
	 * @return EntityDiffObject
	 */
	protected static function newFromMapDiff( MapDiff $mapDiff ) {
		return new static( iterator_to_array( $mapDiff ) );
	}

	/**
	 * @see IDiff::getApplicableDiff
	 *
	 * @since 0.1
	 *
	 * @param array $currentObject
	 *
	 * @return EntityDiffObject
	 */
	public function getApplicableDiff( array $currentObject ) {
		return static::newFromMapDiff( parent::getApplicableDiff( $currentObject ) );
	}

}
