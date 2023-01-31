<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Domain\ReadModel;

use ArrayObject;

/**
 * @license GPL-2.0-or-later
 */
class Descriptions extends ArrayObject {

	public function __construct( Description ...$descriptions ) {
		parent::__construct(
			array_combine(
				array_map( fn( Description $desc ) => $desc->getLanguageCode(), $descriptions ),
				$descriptions
			)
		);
	}
}
