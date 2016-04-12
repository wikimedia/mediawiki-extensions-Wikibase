<?php

namespace Wikibase\DataModel\Term;

/**
 * @since 4.1
 * @deprecated since 5.1, will be removed in 6.0 in favor of FingerprintProvider, which will then
 *  give the guarantee to return an object by reference. Changes to that object change the entity.
 *
 * @license GPL-2.0+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
interface FingerprintHolder extends FingerprintProvider {

	/**
	 * @param Fingerprint $fingerprint
	 */
	public function setFingerprint( Fingerprint $fingerprint );

}
