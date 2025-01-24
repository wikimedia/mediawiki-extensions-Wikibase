<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Services;

use InvalidArgumentException;
use Wikibase\Repo\RestApi\Domain\Services\Exceptions\PatchPathException;
use Wikibase\Repo\RestApi\Domain\Services\Exceptions\PatchTestConditionFailedException;

/**
 * @license GPL-2.0-or-later
 */
interface JsonPatcher {

	/**
	 * @return mixed usually this will be an array, but the type may change if the whole
	 *               root element is modified by the client
	 *
	 * @throws InvalidArgumentException for an invalid patch
	 * @throws PatchPathException if a path target provided in the patch does not exist
	 * @throws PatchTestConditionFailedException if a "test" op in the patch fails
	 */
	public function patch( array $target, array $patch );

}
