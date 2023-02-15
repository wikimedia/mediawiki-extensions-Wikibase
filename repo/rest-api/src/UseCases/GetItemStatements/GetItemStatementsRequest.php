<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItemStatements;

/**
 * @license GPL-2.0-or-later
 */
class GetItemStatementsRequest {

	private string $itemId;
	private ?string $statementPropertyId;

	public function __construct( string $itemId, ?string $statementPropertyId = null ) {
		$this->itemId = $itemId;
		$this->statementPropertyId = $statementPropertyId;
	}

	public function getItemId(): string {
		return $this->itemId;
	}

	public function getStatementPropertyId(): ?string {
		return $this->statementPropertyId;
	}

}
