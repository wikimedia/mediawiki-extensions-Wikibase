<?php

namespace Wikibase;
use Diff\DiffOp;

/**
 * Class for generating Diffs between two Claims.
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

	private $refChange;

	private $mainsnakChange;

	private $rankChange;

	private $qualifierChange;

	/**
	 * Constructor.
	 *
	 */
	public function __construct() {
		$this->refChange = null;
		$this->mainsnakChange = null;
		$this->rankChange = null;
		$this->qualifierChange = null;
	}

	public function getReferencesChange() {
		return $this->refChange;
	}

	public function getMainsnakChange() {
		return $this->mainsnakChange;
	}

	public function getRankChange() {
		return $this->rankChange;
	}

	public function getQualifiersChange() {
		return $this->qualifierChange;
	}

	public function setReferencesChange( DiffOp $op ) {
		$this->refChange = $op;
	}

	public function setMainsnakChange( DiffOp $op ) {
		$this->mainsnakChange = $op;
	}

	public function setRankChange( DiffOp $op ) {
		$this->rankChange = $op;
	}

	public function setQualifiersChange( DiffOp $op ) {
		$this->qualifierChange = $op;
	}
}
