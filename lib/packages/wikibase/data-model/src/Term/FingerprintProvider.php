<?php

namespace Wikibase\DataModel\Term;

/**
 * Common interface for classes (typically Entities) that contain a Fingerprint. Implementations
 * must guarantee this returns the original, mutable object by reference.
 *
 * @since 0.7.3
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface FingerprintProvider {

	/**
	 * This is guaranteed to return the original, mutable object by reference.
	 *
	 * @return Fingerprint
	 */
	public function getFingerprint();

}
