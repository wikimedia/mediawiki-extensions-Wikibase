<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetItemStatements;

use Wikibase\Repo\RestApi\Application\UseCases\ItemIdRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PropertyIdFilterRequest;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseRequest;

/**
 * @license GPL-2.0-or-later
 */
class GetItemStatementsRequest implements UseCaseRequest, ItemIdRequest, PropertyIdFilterRequest {

	private string $itemId;
	private ?string $propertyIdFilter;

	public function __construct( string $itemId, ?string $propertyIdFilter = null ) {
		$this->itemId = $itemId;
		$this->propertyIdFilter = $propertyIdFilter;
	}

	public function getItemId(): string {
		return $this->itemId;
	}

	public function getPropertyIdFilter(): ?string {
		return $this->propertyIdFilter;
	}

}
