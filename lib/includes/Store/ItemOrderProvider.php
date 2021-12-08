<?php
declare( strict_types=1 );

namespace Wikibase\Lib\Store;

/**
 * Interface that contains method for the ItemOrderProvider
 *
 * @license GPL-2.0-or-later
 * @author Noa Rave
 */
interface ItemOrderProvider {

	/**
	 * Get order of items in the form [ $ItemIdSerialization => $ordinalNumber ]
	 *
	 * @return null|int[] An associative array mapping Item ID strings to ordinal numbers.
	 * 	The order of items is represented by the ordinal numbers associated with them.
	 * 	The array is not guaranteed to be sorted.
	 * 	Null if no information exists.
	 * @throws ItemOrderProviderException
	 */
	public function getItemOrder(): ?array;

}
