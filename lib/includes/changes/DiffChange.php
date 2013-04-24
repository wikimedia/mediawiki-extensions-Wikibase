<?php

namespace Wikibase;
use Diff\IDiff;
use Diff\Diff;

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
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class DiffChange extends ChangeRow {

	/**
	 * @since 0.1
	 *
	 * @param string $cache set to 'cache' to cache the unserialized diff.
	 *
	 * @return IDiff
	 */
	public function getDiff( $cache = 'no' ) {
		$info = $this->getInfo( $cache );

		if ( !array_key_exists( 'diff', $info ) ) {
			// This shouldn't happen, but we should be robust against corrupt, incomplete
			// obsolete instances in the database, etc.
			wfLogWarning( 'Cannot get the diff when it has not been set yet.' );
			return new Diff();
		} else {
			return $info['diff'];
		}
	}

	/**
	 * @since 0.4
	 *
	 * @return bool
	 */
	public function hasDiff() {
		$info = $this->getField( 'info' );
		return !empty( $info ) && isset( $info['diff'] );
	}

	/**
	 * @since 0.1
	 *
	 * @param IDiff $diff
	 */
	public function setDiff( IDiff $diff ) {
		$info = $this->hasField( 'info' ) ? $this->getField( 'info' ) : array();
		$info['diff'] = $diff;
		$this->setField( 'info', $info );
	}

	/**
	 * @since 0.1
	 *
	 * @param IDiff $diff
	 * @param array|null $fields
	 *
	 * @return DiffChange
	 */
	public static function newFromDiff( IDiff $diff, array $fields = null ) {
		$instance = new static(
			ChangesTable::singleton(),
			$fields,
			true
		);

		$instance->setDiff( $diff );

		return $instance;
	}

	/**
	 * Returns whether the change is empty.
	 * If it's empty, it can be ignored.
	 *
	 * @since 0.1
	 *
	 * @return boolean
	 */
	public function isEmpty() {
		if ( $this->hasField( 'info' ) ) {
			$info = $this->getField( 'info' );

			if ( array_key_exists( 'diff', $info ) ) {
				return $this->getDiff()->isEmpty();
			}
		}

		return true;
	}

	/**
	 * @see ChangeRow::serializeInfo()
	 *
	 * Overwritten to use the array representation of the diff.
	 *
	 * @since 0.4
	 * @param array $info
	 * @return string
	 */
	public function serializeInfo( array $info ) {
		if ( isset( $info['diff'] ) && $info['diff'] instanceof \Diff\DiffOp ) {
			if ( Settings::get( "changesAsJson" ) === true  ) {
				/* @var \Diff\DiffOp $op */
				$op = $info['diff'];
				$info['diff'] = $op->toArray( array( $this, 'arrayalizeObjects' ) );
			}
		}

		return parent::serializeInfo( $info );
	}

	/**
	 * @see ChangeRow::unserializeInfo()
	 *
	 * Overwritten to use the array representation of the diff.
	 *
	 * @since 0.4
	 * @param string $str
	 * @return array the info array
	 */
	public function unserializeInfo( $str ) {
		static $factory = null;

		if ( $factory == null ) {
			$factory = new WikibaseDiffOpFactory( array( $this, 'objectifyArrays' ) );
		}

		$info = parent::unserializeInfo( $str );

		if ( isset( $info['diff'] ) && is_array( $info['diff'] ) ) {
			$info['diff'] = $factory->newFromArray( $info['diff'] );
		}

		return $info;
	}

	/**
	 * Converts an object to an array structure.
	 * Callback function for use by \Diff\DiffOp::toArray().
	 *
	 * Subclasses should override this to provide array representations of specific value objects.
	 *
	 * @since 0.4
	 *
	 * @param mixed $data
	 * @return mixed
	 */
	public function arrayalizeObjects( $data ) {
		return $data; // noop
	}

	/**
	 * May be overwritten by subclasses to provide special handling.
	 * Callback function for use by \Diff\DiffOpFactory
	 *
	 * Subclasses should override this to reconstruct value objects from arrays.
	 *
	 * @since 0.4
	 *
	 * @param array $data
	 * @return mixed
	 */
	public function objectifyArrays( array $data ) {
		return $data; // noop
	}
}
