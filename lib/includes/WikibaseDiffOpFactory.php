<?php

namespace Wikibase;

/**
 * Class for changes that can be represented as a IDiff.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class WikibaseDiffOpFactory extends \Diff\DiffOpFactory {
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
	 * @todo: pull this up into DiffOpFactory
	 *
	 * @param array $data the input data
	 * @return \Diff\DiffOp[] The diff ops
	 */
	protected function createOperations( array $data ) {
		$operations = array();

		foreach ( $data as $key => $operation ) {
			$operations[$key] = $this->newFromArray( $operation );
		}

		return $operations;
	}
}
