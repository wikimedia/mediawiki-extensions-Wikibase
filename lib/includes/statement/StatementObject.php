<?php

namespace Wikibase;

/**
 * Class representing a Wikibase statement.
 * See https://meta.wikimedia.org/wiki/Wikidata/Data_model#Statements
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
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class StatementObject extends ClaimObject implements Statement {

	/**
	 * @since 0.1
	 *
	 * @var References
	 */
	protected $references;

	/**
	 * @since 0.1
	 *
	 * @var integer, element of the Statement::RANK_ enum
	 */
	protected $rank = Statement::RANK_NORMAL;

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 *
	 * @param Snak $mainSnak
	 * @param Snaks|null $qualifiers
	 * @param References|null $references
	 */
	public function __construct( Snak $mainSnak, Snaks $qualifiers = null, References $references = null ) {
		parent::__construct( $mainSnak, $qualifiers );
		$this->references = $references === null ? new ReferenceList() : $references;
	}

	/**
	 * @see Statement::getReferences
	 *
	 * @since 0.1
	 *
	 * @return References
	 */
	public function getReferences() {
		return $this->references;
	}

	/**
	 * @see Statement::setReferences
	 *
	 * @since 0.1
	 *
	 * @param References $references
	 */
	public function setReferences( References $references ) {
		$this->references = $references;
	}

	/**
	 * @see Statement::setRank
	 *
	 * @since 0.1
	 *
	 * @param integer $rank
	 * @throws \MWException
	 */
	public function setRank( $rank ) {
		$ranks = array( Statement::RANK_DEPRECATED, Statement::RANK_NORMAL, Statement::RANK_PREFERRED );

		if ( !in_array( $rank, $ranks, true ) ) {
			throw new \MWException( 'Invalid rank specified for statement' );
		}

		$this->rank = $rank;
	}

	/**
	 * @see Statement::getRank
	 *
	 * @since 0.1
	 *
	 * @return integer
	 */
	public function getRank() {
		return $this->rank;
	}

	/**
	 * @see Hashable::getHash
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getHash() {
		$hasher = new MapValueHasher();

		return sha1( implode(
			'|',
			array(
				parent::getHash(),
				$this->rank,
				$hasher->hash( $this->references ),
			)
		) );
	}

}
