<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedPropertyFieldsRequest {
	public function getPropertyFields(): array;
}
