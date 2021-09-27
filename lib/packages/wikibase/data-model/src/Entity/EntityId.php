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

}
