<?php

namespace Wikibase;

/**
 * Interface for objects that represent a single Wikibase statement.
 * See https://meta.wikimedia.org/wiki/Wikidata/Data_model#Statements
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface Statement {

	/**
	 * Rank enum. Higher values are more preferred.
	 *
	 * @since 0.1
	 */
	const RANK_PREFERRED = 2;
	const RANK_NORMAL = 1;
	const RANK_DEPRECATED = 0;

	/**
	 * Adds a new reference to the statement.
	 * The returned reference can be used to generate a reference hash.
	 *
	 * @since 0.1
	 *
	 * @param Reference $reference
	 *
	 * @return Reference
	 */
	public function addReference( Reference $reference );

	/**
	 * Updates the reference with provided hash to the provided reference.
	 *
	 * @since 0.1
	 *
	 * @param string $referenceHash
	 * @param Reference $reference
	 *
	 * @return Reference
	 */
	public function updateReference( $referenceHash, Reference $reference );

	/**
	 * Removes the reference with provided hash, if it exists.
	 *
	 * @since 0.1
	 *
	 * @param string $referenceHash
	 */
	public function removeReference( $referenceHash );

	/**
	 * Returns if the statement has a reference with the provided hash.
	 *
	 * @since 0.1
	 *
	 * @param string $referenceHash
	 *
	 * @return boolean
	 */
	public function hasReference( $referenceHash );

	/**
	 * Sets the rank of the statement.
	 * The rank is an element of the Statement::RANK_ enum.
	 *
	 * @since 0.1
	 *
	 * @param integer $rank
	 */
	public function setRank( $rank );

	/**
	 * Gets the rank of the statement.
	 * The rank is an element of the Statement::RANK_ enum.
	 *
	 * @since 0.1
	 *
	 * @return integer
	 */
	public function getRank();

	/**
	 * Returns the claim of the statement.
	 *
	 * @since 0.1
	 *
	 * @return Claim
	 */
	public function getClaim();

	/**
	 * Returns a unique hash for the statement.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getHash();

	/**
	 * Returns the number of the statement needed to identify it within an entity.
	 *
	 * @since 0.1
	 *
	 * @return integer
	 */
	public function getNumber();

}