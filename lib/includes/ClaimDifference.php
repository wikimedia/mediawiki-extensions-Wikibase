<?php

namespace Wikibase;
use Diff\DiffOp;

/**
 * Class for representing Diff between two Claims.
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
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */

class ClaimDifference {

	/**
	 * @since 0.4
	 *
	 * @var DiffOp
	 */
	private $refChange;

	/**
	 * @since 0.4
	 *
	 * @var DiffOp
	 */
	private $mainsnakChange;

	/**
	 * @since 0.4
	 *
	 * @var DiffOp
	 */
	private $rankChange;

	/**
	 * @since 0.4
	 *
	 * @var DiffOp
	 */
	private $qualifierChange;

	/**
	 * Constructor.
	 *
	 * @since 0.4
	 */
	public function __construct() {
		$this->refChange = null;
		$this->mainsnakChange = null;
		$this->rankChange = null;
		$this->qualifierChange = null;
	}

	/**
	 * Returns the reference change.
	 *
	 * @since 0.4
	 *
	 * @return DiffOp
	 */
	public function getReferencesChange() {
		return $this->refChange;
	}

	/**
	 * Returns the mainsnak change.
	 *
	 * @since 0.4
	 *
	 * @return DiffOp
	 */
	public function getMainsnakChange() {
		return $this->mainsnakChange;
	}

	/**
	 * Returns the rank change.
	 *
	 * @since 0.4
	 *
	 * @return DiffOp
	 */
	public function getRankChange() {
		return $this->rankChange;
	}

	/**
	 * Returns the qualifier change.
	 *
	 * @since 0.4
	 *
	 * @return DiffOp
	 */
	public function getQualifiersChange() {
		return $this->qualifierChange;
	}

	/**
	 * Sets the reference change.
	 *
	 * @since 0.4
	 *
	 * @param DiffOp $op
	 */
	public function setReferencesChange( DiffOp $op ) {
		$this->refChange = $op;
	}

	/**
	 * Sets the mainsnak change.
	 *
	 * @since 0.4
	 *
	 * @param DiffOp $op
	 */
	public function setMainsnakChange( DiffOp $op ) {
		$this->mainsnakChange = $op;
	}

	/**
	 * Sets the rank change.
	 *
	 * @since 0.4
	 *
	 * @param DiffOp $op
	 */
	public function setRankChange( DiffOp $op ) {
		$this->rankChange = $op;
	}

	/**
	 * Sets the qualifier change.
	 *
	 * @since 0.4
	 *
	 * @param DiffOp $op
	 */
	public function setQualifiersChange( DiffOp $op ) {
		$this->qualifierChange = $op;
	}
}
