<?php

namespace Wikibase\DataModel\Claim;

/**
 * Interface for objects that contain Claim objects to create an external Claims object.
 *
 * @since 0.2
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface ClaimAggregate {

	/**
	 * Returns the claims contained by this ClaimAggregate.
	 * This is a read-only interface. You should not modify
	 * claims obtained through this interface without cloning
	 * them first.
	 *
	 * @since 0.2
	 *
	 * @return Claim[]
	 */
	public function getClaims();

}
