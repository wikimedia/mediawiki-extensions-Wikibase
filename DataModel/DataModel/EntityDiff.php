<?php

namespace Wikibase;

use Diff\Diff;

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
 * @ingroup WikibaseLib
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
	 * @return EntityDiff
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
	 * @param \Diff\DiffOp[] $operations
	 */
	public function __construct( array $operations = array() ) {
		parent::__construct( $operations, true );
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
	 * @see DiffOp::getType();
	 *
	 * @return string 'diff/entity'
	 */
	public function getType() {
		return 'diff/entity'; // generic
	}
}
