<?php

namespace Wikibase\Lib\Store;

use InvalidArgumentException;
use MWException;
use OutOfBoundsException;
use Title;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Represents an arbitrary mapping from entity IDs to wiki page titles, with no further guarantees
 * given. The resulting title does not necessarily represent the page that actually stores the
 * entity contents.
 *
 * The mapping could be programmatic, or it could be based on database lookups.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
interface EntityTitleLookup {

	/**
	 * Returns the Title for the given entity.
	 *
	 * If the entity does not exist, this method will return either null,
	 * or a Title object referring to a page that does not exist.
	 *
	 * @todo change this to return a TitleValue
	 *
	 * @param EntityId $id
	 *
	 * @throws MWException
	 * @throws OutOfBoundsException
	 * @throws InvalidArgumentException
	 * @return Title|null
	 */
	public function getTitleForId( EntityId $id );

	/**
	 * Returns an array of Titles for the given entity ids, indexed by the id serialization
	 *
	 * If an entity does not exist, the array element for the relevant id serialization will be null
	 *
	 * @param EntityId[] $ids
	 *
	 * @throws MWException
	 * @throws OutOfBoundsException
	 * @throws InvalidArgumentException
	 * @return (Title|null)[]
	 */
	public function getTitlesForIds( array $ids );

}
