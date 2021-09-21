<?php

namespace Wikibase\DataModel\Services\Fixtures;

use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;

/**
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyFixtures {

	public static function newProperty() {
		return new Property(
			new NumericPropertyId( 'P1' ),
			null,
			'string'
		);
	}

}
