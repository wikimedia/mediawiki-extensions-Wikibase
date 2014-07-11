<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\Fingerprint;

/**
 * Service interface for accessing entity Fingerprints.
 *
 * Note that an EntityFingerprintLookup does not need to be backed by storage.
 * It may just as well represent some arbitrary set of Fingerprints.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
interface EntityFingerprintLookup {

	/**
	 * Returns the Fingerprint associated with the given entity.
	 *
	 * Implementations may use the $languages and $termTypes parameters for optimization.
	 * It is however not guaranteed that other languages and types are absent from the
	 * resulting Fingerprint.
	 *
	 * @since 0.5
	 *
	 * @param EntityId $entityId
	 * @param string[]|null $languages A list of language codes we are interested in. Null means any.
	 * @param string[]|null $termTypes A list of term types (like "label", "description", or "alias")
	 *        we are interested in. Null means any.
	 *
	 * @throw StorageException
	 * @return Fingerprint|null The entity's fingerprint, or null if the entity is not known
	 *         to this lookup.
	 */
	public function getFingerprint( EntityId $entityId, array $languages = null, array $termTypes = null );

}
