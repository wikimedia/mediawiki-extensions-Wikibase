<?php

namespace Wikibase\DataModel\Entity;

/**
 * Interface for Entity objects that can be cleared.
 *
 * @since 7.5
 *
 * @license GPL-2.0-or-later
 */
interface ClearableEntity {

	/**
	 * Clears all fields of the entity that can be emptied. The entity's id stays the same.
	 *
	 * @since 7.5
	 */
	public function clear();

}
