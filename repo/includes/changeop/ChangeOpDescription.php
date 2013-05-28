<?php

namespace Wikibase;

use Diff\DiffOp;
use Diff\DiffOpRemove;
use InvalidArgumentException;

/**
 * Class for description change operation
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
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class ChangeOpDescription implements ChangeOp {

	/**
	 * @since 0.4
	 *
	 * @var string
	 */
	protected $language;

	/**
	 * @since 0.4
	 *
	 * @var DiffOp
	 */
	protected $diffOp;

	/**
	 * @since 0.4
	 *
	 * @param string $language
	 * @param DiffOp $diffOp
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $language, DiffOp $diffOp ) {
		if ( !is_string( $language ) ) {
			throw new InvalidArgumentException( '$language needs to be a string' );
		}

		$this->language = $language;
		$this->diffOp = $diffOp;
	}

	/**
	 * Applies the change to the given entity
	 *
	 * @since 0.4
	 *
	 * @param Entity $entity
	 */
	public function apply( Entity $entity ) {
		if ( $this->diffOp instanceof DiffOpRemove ) {
			$entity->removeDescription( $this->language );
		} else {
			$entity->setDescription( $this->language, $this->diffOp->getNewValue() );
		}
	}

}
