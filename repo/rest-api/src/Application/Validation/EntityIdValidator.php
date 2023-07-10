<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Validation;

/**
 * @license GPL-2.0-or-later
 */
interface EntityIdValidator {

	public function validate( string $entityId ): ?ValidationError;

}
