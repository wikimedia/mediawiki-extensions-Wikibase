<?php

namespace Wikibase;

/**
 * Interface for objects that can be accessed as a list of Claim objects.
 *
 * @since 0.2
 *
 * @file
 * @ingroup WikibaseDataModel
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface ClaimListAccess {

	/**
	 * Adds the provided claims to the list.
	 *
	 * @since 0.2
	 *
	 * @param Claim $claim
	 */
	public function addClaim( Claim $claim );

	/**
	 * Returns if the list contains a claim with the same hash as the provided claim.
	 *
	 * @since 0.2
	 *
	 * @param Claim $claim
	 *
	 * @return boolean
	 */
	public function hasClaim( Claim $claim );

	/**
	 * Removes the claim with the same hash as the provided claim if such a claim exists in the list.
	 *
	 * @since 0.2
	 *
	 * @param Claim $claim
	 */
	public function removeClaim( Claim $claim );

	/**
	 * Returns if the list contains a claim with the the provided GUID.
	 *
	 * @since 0.3
	 *
	 * @param string $claimGuid
	 *
	 * @return boolean
	 */
	public function hasClaimWithGuid( $claimGuid );

	/**
	 * Removes the claim with the provided GUID if such a claim exists in the list.
	 *
	 * @since 0.3
	 *
	 * @param string $claimGuid
	 */
	public function removeClaimWithGuid( $claimGuid );

	/**
	 * Returns the claim with the provided GUID or null if there is no such claim.
	 *
	 * @since 0.3
	 *
	 * @param string $claimGuid
	 *
	 * @return Claim|null
	 */
	public function getClaimWithGuid( $claimGuid );

}
