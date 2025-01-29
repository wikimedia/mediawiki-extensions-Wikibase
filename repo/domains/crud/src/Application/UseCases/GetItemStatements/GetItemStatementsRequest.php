<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemStatements;

use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\ItemIdRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\PropertyIdFilterRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\UseCaseRequest;

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
