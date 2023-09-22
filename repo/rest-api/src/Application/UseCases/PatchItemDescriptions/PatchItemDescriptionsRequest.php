<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchItemDescriptions;

use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\ItemIdRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\PatchRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\UseCaseRequest;

/**
 * @license GPL-2.0-or-later
 */
class PatchItemDescriptionsRequest implements UseCaseRequest, ItemIdRequest, PatchRequest {

	private string $itemId;
	private array $patch;

	public function __construct(
		string $itemId,
		array $patch
	) {
		$this->itemId = $itemId;
		$this->patch = $patch;
	}

	public function getItemId(): string {
		return $this->itemId;
	}

	public function getPatch(): array {
		return $this->patch;
	}

}
