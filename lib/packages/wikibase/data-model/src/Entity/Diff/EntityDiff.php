<?php

namespace Wikibase\DataModel\Entity\Diff;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOp;
use Wikibase\DataModel\Entity\Item;

/**
 * Represents a diff between two Entity instances.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityDiff extends Diff {

	/**
	 * @since 0.4
	 *
	 * @param string $entityType
	 * @param \Diff\DiffOp[] $operations
	 *
	 * @return self
	 */
	public static function newForType( $entityType, $operations = array() ) {
		if ( $entityType === Item::ENTITY_TYPE ) {
			return new ItemDiff( $operations );
		}
		else {
			return new EntityDiff( $operations );
		}
	}

	/**
	 * Constructor.
	 *
	 * @param DiffOp[] $operations
	 */
	public function __construct( array $operations = array() ) {
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

		if ( !$operations[$key] instanceof Diff ) {
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
			$operations[$key] = new Diff( array(), true );
		}
	}

	/**
	 * Returns a Diff object with the aliases differences.
	 *
	 * @since 0.1
	 *
	 * @return Diff
	 */
	public function getAliasesDiff() {
		return isset( $this['aliases'] ) ? $this['aliases'] : new Diff( array(), true );
	}

	/**
	 * Returns a Diff object with the labels differences.
	 *
	 * @since 0.1
	 *
	 * @return Diff
	 */
	public function getLabelsDiff() {
		return isset( $this['label'] ) ? $this['label'] : new Diff( array(), true );
	}

	/**
	 * Returns a Diff object with the descriptions differences.
	 *
	 * @since 0.1
	 *
	 * @return Diff
	 */
	public function getDescriptionsDiff() {
		return isset( $this['description'] ) ? $this['description'] : new Diff( array(), true );
	}

	/**
	 * Returns a Diff object with the claim differences.
	 *
	 * @since 0.4
	 *
	 * @return Diff
	 */
	public function getClaimsDiff() {
		return isset( $this['claim'] ) ? $this['claim'] : new Diff( array(), true );
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
			&& $this->getLabelsDiff()->isEmpty()
			&& $this->getClaimsDiff()->isEmpty();
	}

	/**
	 * @see DiffOp::getType
	 *
	 * @return string
	 */
	public function getType() {
		return 'diff/entity';
	}

}
