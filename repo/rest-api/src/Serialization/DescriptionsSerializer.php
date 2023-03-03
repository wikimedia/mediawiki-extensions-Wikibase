<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Serialization;

use ArrayObject;
use Wikibase\Repo\RestApi\Domain\ReadModel\Descriptions;

/**
 * @license GPL-2.0-or-later
 */
class DescriptionsSerializer {

	public function serialize( Descriptions $descriptions ): ArrayObject {
		$serialization = new ArrayObject();
		foreach ( $descriptions as $languageCode => $description ) {
			$serialization[$languageCode] = $description->getText();
		}
		return $serialization;
	}

}
