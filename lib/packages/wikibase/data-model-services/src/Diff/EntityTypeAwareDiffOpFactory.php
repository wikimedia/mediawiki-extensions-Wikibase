<?php

namespace Wikibase\DataModel\Services\Diff;

use Diff\DiffOp\DiffOp;
use Diff\DiffOpFactory;
use InvalidArgumentException;

/**
 * Class for changes that can be represented as a Diff.
 *
 * @since 1.2
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class EntityTypeAwareDiffOpFactory extends DiffOpFactory {

	/**
	 * @param array $diffOp
	 *
	 * @return DiffOp
	 * @throws InvalidArgumentException
	 */
	public function newFromArray( array $diffOp ) {
		$this->assertHasKey( 'type', $diffOp );

		// see EntityDiff::getType() and ItemDiff::getType()
		if ( preg_match( '!^diff/(.*)$!', $diffOp['type'], $matches ) ) {
			$itemType = $matches[1];
			$this->assertHasKey( 'operations', $diffOp );

			$operations = $this->createOperations( $diffOp['operations'] );
			$diff = EntityDiff::newForType( $itemType, $operations );

			return $diff;
		}

		return parent::newFromArray( $diffOp );
	}

	/**
	 * Converts a list of diff operations represented by arrays into a list of
	 * DiffOp objects.
	 *
	 * @param array $data the input data
	 * @return DiffOp[] The diff ops
	 */
	private function createOperations( array $data ) {
		$operations = [];

		foreach ( $data as $key => $operation ) {
			$operations[$key] = $this->newFromArray( $operation );
		}

		return $operations;
	}

}
