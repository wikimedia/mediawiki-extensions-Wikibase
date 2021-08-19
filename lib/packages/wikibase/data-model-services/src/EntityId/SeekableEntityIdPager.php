<?php

namespace Wikibase\DataModel\Services\EntityId;

/**
 * The position markers are implementation dependent and are not
 * interchangeable between different implementations.
 *
 * @since 3.14
 *
 * @license GPL-2.0-or-later
 */
interface SeekableEntityIdPager extends EntityIdPager {

	/**
	 * @return mixed Round trips with method setPosition
	 */
	public function getPosition();

	/**
	 * @param mixed $position Round trips with method getPosition
	 */
	public function setPosition( $position );

}
