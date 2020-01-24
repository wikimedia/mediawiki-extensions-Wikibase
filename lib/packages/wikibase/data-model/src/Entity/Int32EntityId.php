<?php

namespace Wikibase\DataModel\Entity;

/**
 * Interface for EntityIds that can be converted to a positive, signed 32 bit integer in the range
 * [1..2147483647], and back from the entity type and the number. The number must be distinct for
 * different IDs. When an ID can not be represented as a distinct integer, an
 * InvalidArgumentException must be thrown on construction time. For example, when "Q1" and "Q01"
 * have the same integer representation, only one should be allowed.
 *
 * Entity types that do not meet this criteria should not implement this interface.
 *
 * Entity types are not required and not guaranteed to implement this interface. Use the full string
 * serialization whenever you can and avoid using numeric IDs.
 *
 * @since 6.1
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
interface Int32EntityId {

	public const MAX = 2147483647;

	/**
	 * @since 6.1
	 *
	 * @return int Guaranteed to be a distinct integer in the range [1..2147483647].
	 */
	public function getNumericId();

}
