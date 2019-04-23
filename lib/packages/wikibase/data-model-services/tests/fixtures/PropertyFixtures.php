<?php

namespace Wikibase\DataModel\Services\Fixtures;

use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyFixtures {

	public static function newProperty() {
		return new Property(
			new PropertyId( 'P1' ),
			null,
			'string'
		);
	}

}
