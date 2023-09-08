<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases;

use Wikibase\DataModel\Entity\NumericPropertyId;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedPropertyIdRequest {
	public function getPropertyId(): NumericPropertyId;
}
