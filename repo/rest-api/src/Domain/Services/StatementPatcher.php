<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Services;

use InvalidArgumentException;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\RestApi\Domain\Exceptions\InapplicablePatchException;
use Wikibase\Repo\RestApi\Domain\Exceptions\InvalidPatchedSerializationException;
use Wikibase\Repo\RestApi\Domain\Exceptions\InvalidPatchedStatementException;
use Wikibase\Repo\RestApi\Domain\Exceptions\PatchTestConditionFailedException;

/**
 * @license GPL-2.0-or-later
 */
interface StatementPatcher {

	/**
	 * @throws InvalidArgumentException for an invalid patch
	 * @throws PatchTestConditionFailedException if a "test" op in the patch fails
	 * @throws InapplicablePatchException if the patch cannot be applied
	 * @throws InvalidPatchedSerializationException if the patch result cannot be deserialized to a valid statement
	 * @throws InvalidPatchedStatementException if the patch result can be deserialized, but yields an otherwise invalid statement
	 */
	public function patch( Statement $statement, array $patch ): Statement;

}
