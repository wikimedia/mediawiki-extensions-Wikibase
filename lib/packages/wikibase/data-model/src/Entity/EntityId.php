<?php

namespace Wikibase\DataModel\Entity;

use Serializable;

/**
 * @license GPL-2.0-or-later
 */
interface EntityId extends Serializable {

	/**
	 * @return string
	 */
	public function getEntityType();

	/**
	 * @return string
	 */
	public function getSerialization();

	/**
	 * TODO: Consider removing this method in favor of just always calling getSerialization().
	 *
	 * @return string
	 */
	public function __toString();

	/**
	 * @param mixed $target
	 * @return bool
	 */
	public function equals( $target );

	/**
	 * TODO: This method shouldn't exist on this interface as it doesn't make sense for certain types of IDs. It should be moved to a
	 *       separate interface, or removed altogether.
	 *
	 * @return string
	 */
	public function getLocalPart();

	/**
	 * TODO: This method shouldn't exist on this interface as it doesn't make sense for certain types of IDs. It should be moved to a
	 *       separate interface, or removed altogether.
	 *
	 * @return string
	 */
	public function getRepositoryName();

	/**
	 * This method replaces {@link Serializable::serialize()}.
	 * Do not call it manually.
	 * Also, consider using {@link getSerialization()} and an {@link EntityIdParser}
	 * instead of PHP serialization.
	 *
	 * @see https://www.php.net/manual/en/language.oop5.magic.php#object.serialize
	 */
	public function __serialize(): array;

	/**
	 * This method replaces {@link Serializable::unserialize()}.
	 * Do not call it manually.
	 * Also, consider using {@link getSerialization()} and an {@link EntityIdParser}
	 * instead of PHP serialization.
	 *
	 * @see https://www.php.net/manual/en/language.oop5.magic.php#object.unserialize
	 */
	public function __unserialize( array $data );

}
