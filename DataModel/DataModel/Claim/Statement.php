<?php

namespace Wikibase;

use InvalidArgumentException;

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
 * @ingroup WikibaseDataModel
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class Statement extends Claim {

	/**
	 * Rank enum. Higher values are more preferred.
	 *
	 * @since 0.1
	 */
	const RANK_PREFERRED = 2;
	const RANK_NORMAL = 1;
	const RANK_DEPRECATED = 0;

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
	 * Returns the references attached to this statement.
	 *
	 * @since 0.1
	 *
	 * @return References
	 */
	public function getReferences() {
		return $this->references;
	}

	/**
	 * Sets the references attached to this statement.
	 *
	 * @since 0.1
	 *
	 * @param References $references
	 */
	public function setReferences( References $references ) {
		$this->references = $references;
	}

	/**
	 * Sets the rank of the statement.
	 * The rank is an element of the Statement::RANK_ enum.
	 *
	 * @since 0.1
	 *
	 * @param integer $rank
	 * @throws InvalidArgumentException
	 */
	public function setRank( $rank ) {
		$ranks = array( Statement::RANK_DEPRECATED, Statement::RANK_NORMAL, Statement::RANK_PREFERRED );

		if ( !in_array( $rank, $ranks, true ) ) {
			throw new InvalidArgumentException( 'Invalid rank specified for statement: ' . var_export( $rank, true ) );
		}

		$this->rank = $rank;
	}

	/**
	 * Gets the rank of the statement.
	 * The rank is an element of the Statement::RANK_ enum.
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
		return sha1( implode(
			'|',
			array(
				parent::getHash(),
				$this->rank,
				$this->references->getValueHash(),
			)
		) );
	}

	/**
	 * @see Claim::toArray
	 *
	 * @since 0.3
	 *
	 * @return array
	 */
	public function toArray() {
		$data = parent::toArray();

		$data['rank'] = $this->rank;
		$data['refs'] = $this->references->toArray();

		return $data;
	}

	/**
	 * Constructs a new Statement from an array in the same format as Claim::toArray returns.
	 *
	 * @since 0.3
	 *
	 * @param array $data
	 *
	 * @return Statement
	 */
	public static function newFromArray( array $data ) {
		$rank = $data['rank'];
		unset( $data['rank'] );

		/**
		 * @var Statement $statement
		 */
		$statement = parent::newFromArray( $data );

		$statement->setRank( $rank );
		$statement->setReferences( ReferenceList::newFromArray( $data['refs'] ) );

		return $statement;
	}

	/**
	 * @see Serializable::unserialize
	 *
	 * @since 0.3
	 *
	 * @param string $serialization
	 *
	 * @return Statement
	 */
	public function unserialize( $serialization ) {
		$instance = static::newFromArray( json_decode( $serialization, true ) );

		$this->setMainSnak( $instance->getMainSnak() );
		$this->setQualifiers( $instance->getQualifiers() );
		$this->setGuid( $instance->getGuid() );
		$this->setRank( $instance->getRank() );
		$this->setReferences( $instance->getReferences() );
	}

}

/**
 * @deprecated
 */
class StatementObject extends Statement {}