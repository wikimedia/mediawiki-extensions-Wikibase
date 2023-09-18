<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetItemStatement;

use Wikibase\Repo\RestApi\Application\UseCases\GetStatement\GetStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\ItemIdRequest;

/**
 * @license GPL-2.0-or-later
 */
class GetItemStatementRequest extends GetStatementRequest implements ItemIdRequest {

	private string $itemId;

	public function __construct( string $propertyId, string $statementId ) {
		parent::__construct( $statementId );
		$this->itemId = $propertyId;
	}

	public function getItemId(): string {
		return $this->itemId;
	}
}
