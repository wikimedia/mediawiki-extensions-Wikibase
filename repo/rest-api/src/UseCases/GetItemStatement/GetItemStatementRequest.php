<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItemStatement;

/**
 * @license GPL-2.0-or-later
 */
class GetItemStatementRequest {

	private string $statementId;
	private ?string $itemId;

	public function __construct( string $statementId, string $itemId = null ) {
		$this->statementId = $statementId;
		$this->itemId = $itemId;
	}

	public function getStatementId(): string {
		return $this->statementId;
	}

	public function getItemId(): ?string {
		return $this->itemId;
	}
}
