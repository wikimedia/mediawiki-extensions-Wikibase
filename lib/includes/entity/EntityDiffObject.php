<?php

namespace Wikibase;

/**
 * Represents a diff between two Wikibase\Entity instances.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
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
	 * Returns a MapDiff object with the aliases differences.
	 *
	 * @since 0.1
	 *
	 * @return MapDiff
	 */
	public function getAliasesDiff() {
		return $this->operations['aliases'];
	}

	/**
	 * Returns a MapDiff object with the labels differences.
	 *
	 * @since 0.1
	 *
	 * @return MapDiff
	 */
	public function getLabelsDiff() {
		return $this->operations['labels'];
	}

	/**
	 * Returns a MapDiff object with the descriptions differences.
	 *
	 * @since 0.1
	 *
	 * @return MapDiff
	 */
	public function getDescriptionsDiff() {
		return $this->operations['descriptions'];
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
