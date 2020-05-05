<?php

declare( strict_types = 1 );
namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;

/**
 * @license GPL-2.0-or-later
 */
interface EntityUrlLookup {

	/**
	 * Always returns the full URL.
	 *
	 * Example: https://www.wikidata.org/wiki/Property:P123
	 *
	 * @param EntityId $id
	 * @return string|null
	 */
	public function getFullUrl( EntityId $id ): ?string;

	/**
	 * Get a URL that's the simplest URL that will be valid to link, locally,
	 * to the current Entity.
	 *
	 * Example Contexts:
	 *  - Regular Wikibase Entities will normally want to return relative URLs.
	 *  - Entities on remote Wikibases probably always want to return full URLs.
	 *
	 * Example Values: /wiki/Property:P123 or similar to getFullUrl()
	 *
	 * @param EntityId $id
	 * @return string|null
	 */
	public function getLinkUrl( EntityId $id ): ?string;

}
