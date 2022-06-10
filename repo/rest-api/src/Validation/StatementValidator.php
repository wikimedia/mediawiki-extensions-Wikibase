<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Validation;

use Wikibase\DataModel\Statement\Statement;

/**
 * @license GPL-2.0-or-later
 */
interface StatementValidator {

	public function validate( array $statementSerialization, string $source ): ?ValidationError;

	/**
	 * Returns the Statement object which is deserialized during the validation.
	 * This method only returns the validated Statement if the validation didn't error.
	 */
	public function getValidatedStatement(): ?Statement;

}
