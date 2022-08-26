<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Services;

use InvalidArgumentException;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\RestApi\Domain\Exceptions\InapplicablePatchException;
use Wikibase\Repo\RestApi\Domain\Exceptions\InvalidPatchedSerializationException;
use Wikibase\Repo\RestApi\Domain\Exceptions\PatchTestConditionFailedException;

/**
 * @license GPL-2.0-or-later
 */
interface StatementPatcher {

	/**
	 * @throws InvalidArgumentException for an invalid patch
	 * @throws InvalidPatchedSerializationException if the patch result is not a valid statement serialization
	 * @throws PatchTestConditionFailedException if a "test" op in the patch fails
	 * @throws InapplicablePatchException if the patch cannot be applied
	 */
	public function patch( Statement $statement, array $patch ): Statement;

}
