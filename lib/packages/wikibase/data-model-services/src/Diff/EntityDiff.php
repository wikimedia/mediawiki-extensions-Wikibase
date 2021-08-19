<?php

namespace Wikibase\DataModel\Services\Diff;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOp;
use Wikibase\DataModel\Entity\Item;

/**
 * Represents a diff between two entities.
 *
 * @since 1.0
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityDiff extends Diff {

	/**
	 * @param string $entityType
	 * @param DiffOp[] $operations
	 *
	 * @return self
	 */
	public static function newForType( $entityType, array $operations = [] ) {
		if ( $entityType === Item::ENTITY_TYPE ) {
			return new ItemDiff( $operations );
		} else {
			return new self( $operations );
		}
	}

	/**
	 * @param DiffOp[] $operations
	 */
	public function __construct( array $operations = [] ) {
		$this->fixSubstructureDiff( $operations, 'aliases' );
		$this->fixSubstructureDiff( $operations, 'label' );
		$this->fixSubstructureDiff( $operations, 'description' );
		$this->fixSubstructureDiff( $operations, 'claim' );

		parent::__construct( $operations, true );
	}

	/**
	 * Checks the type of a substructure diff, and replaces it if needed.
	 * This is needed for backwards compatibility with old versions of
	 * MapDiffer: As of commit ff65735a125e, MapDiffer may generate atomic diffs for
	 * substructures even in recursive mode (bug 51363).
	 *
	 * @param array &$operations All change ops; This is a reference, so the
	 *        substructure diff can be replaced if need be.
	 * @param string $key The key of the substructure
	 */
	protected function fixSubstructureDiff( array &$operations, $key ) {
		if ( !isset( $operations[$key] ) ) {
			return;
		}

		if ( !( $operations[$key] instanceof Diff ) ) {
			$warning = "Invalid substructure diff for key $key: " . get_class( $operations[$key] );

			if ( function_exists( 'wfLogWarning' ) ) {
				wfLogWarning( $warning );
			} else {
				trigger_error( $warning, E_USER_WARNING );
			}

			// We could look into the atomic diff, see if it uses arrays as values,
			// and construct a new Diff according to these values. But since the
			// actual old behavior of MapDiffer didn't cause that to happen, let's
			// just use an empty diff, which is what MapDiffer should have returned
			// in the actual broken case mentioned in bug 51363.
			$operations[$key] = new Diff( [], true );
		}
	}

	/**
	 * FIXME: Not all entities do have aliases!
	 *
	 * Returns a Diff object with the aliases differences.
	 *
	 * @return Diff
	 */
	public function getAliasesDiff() {
		return isset( $this['aliases'] ) ? $this['aliases'] : new Diff( [], true );
	}

	/**
	 * FIXME: Not all entities do have labels!
	 *
	 * Returns a Diff object with the labels differences.
	 *
	 * @return Diff
	 */
	public function getLabelsDiff() {
		return isset( $this['label'] ) ? $this['label'] : new Diff( [], true );
	}

	/**
	 * FIXME: Not all entities do have descriptions!
	 *
	 * Returns a Diff object with the descriptions differences.
	 *
	 * @return Diff
	 */
	public function getDescriptionsDiff() {
		return isset( $this['description'] ) ? $this['description'] : new Diff( [], true );
	}

	/**
	 * FIXME: Not all entities do have claims a.k.a. statements!
	 *
	 * Returns a Diff object with the claim differences.
	 *
	 * @return Diff
	 */
	public function getClaimsDiff() {
		return isset( $this['claim'] ) ? $this['claim'] : new Diff( [], true );
	}

	/**
	 * Returns if there are any changes (equivalent to: any differences between the entities).
	 *
	 * @return bool
	 */
	public function isEmpty(): bool {
		return $this->getLabelsDiff()->isEmpty()
			&& $this->getDescriptionsDiff()->isEmpty()
			&& $this->getAliasesDiff()->isEmpty()
			&& $this->getClaimsDiff()->isEmpty();
	}

	/**
	 * @see DiffOp::getType
	 *
	 * @return string
	 */
	public function getType(): string {
		return 'diff/entity';
	}

}
