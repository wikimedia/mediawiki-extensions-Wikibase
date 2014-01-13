<?php

namespace Wikibase;

/**
 * Class for changes that can be represented as a IDiff.
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
