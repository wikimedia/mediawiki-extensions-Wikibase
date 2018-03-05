<?php

namespace Wikibase\Lib\Store;

/**
 * PropertyOrderProvider that uses one of two given providers:
 * It first tries the primary provider and, if that has no data, resorts to
 * the secondary provider.
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class FallbackPropertyOrderProvider implements PropertyOrderProvider {

	/**
	 * @var PropertyOrderProvider
	 */
	private $primaryProvider;

	/**
	 * @var PropertyOrderProvider
	 */
	private $secondaryProvider;

	public function __construct(
		PropertyOrderProvider $primaryProvider,
		PropertyOrderProvider $secondaryProvider
	) {
		$this->primaryProvider = $primaryProvider;
		$this->secondaryProvider = $secondaryProvider;
	}

	/**
	 * @see PropertyOrderProvider::getPropertyOrder
	 * @return int[]|null
	 */
	public function getPropertyOrder() {
		$propertyOrder = $this->primaryProvider->getPropertyOrder();

		if ( $propertyOrder === null ) {
			$propertyOrder = $this->secondaryProvider->getPropertyOrder();
		}

		return $propertyOrder;
	}

}
