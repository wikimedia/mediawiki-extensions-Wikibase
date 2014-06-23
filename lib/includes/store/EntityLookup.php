<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Contains methods for interaction with an entity store.
 *
 * @since 0.3
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
interface EntityLookup {

	/**
	 * Constant indicating that redirects should not be followed,
	 * for use with getEntity() and hasEntity(). Specifies that
	 * zero levels of redirects should be followed.
	 */
	const REDIRECT_NO = 0;

	/**
	 * Returns the entity with the provided id or null if there is no such
	 * entity. If a $revision is given, the requested revision of the entity is loaded.
	 * If that revision does not exist or does not belong to the given entity,
	 * an exception is thrown.
	 *
	 * @since 0.3
	 *
	 * @param EntityId $entityId
	 * @param int $followRedirects How many levels of redirects to follow. If more
	 * redirects are encountered than specified by $followRedirects, an
	 * UnresolvedRedirectException is thrown.
	 *
	 * @throw StorageException
	 * @return Entity|null
	 */
	public function getEntity( EntityId $entityId, $followRedirects = 1 );

	/**
	 * Returns whether the given entity can bee looked up using
	 * getEntity(). This avoids loading and deserializing entity content
	 * just to check whether the entity exists.
	 *
	 * @note Under some circumstances, getEntity() may return null even when hasEntity()
	 * has returned true for a given entity. This may happen e.g. if the entity was deleted
	 * just between the time hasEntity() was called and the call to getEntity().
	 *
	 * @since 0.4
	 *
	 * @param EntityId $entityId
	 * @param int $followRedirects How many levels of redirects to follow. If more
	 * redirects are encountered than specified by $followRedirects, an
	 * UnresolvedRedirectException is thrown.
	 *
	 * @throw StorageException
	 * @return bool
	 */
	public function hasEntity( EntityId $entityId, $followRedirects = 1 );

}
