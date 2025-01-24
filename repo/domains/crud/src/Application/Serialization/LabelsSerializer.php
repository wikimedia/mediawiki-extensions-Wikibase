<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\Serialization;

use ArrayObject;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Labels;

/**
 * @license GPL-2.0-or-later
 */
class LabelsSerializer {

	public function serialize( Labels $labels ): ArrayObject {
		$serialization = new ArrayObject();
		foreach ( $labels as $languageCode => $label ) {
			$serialization[$languageCode] = $label->getText();
		}
		return $serialization;
	}

}
