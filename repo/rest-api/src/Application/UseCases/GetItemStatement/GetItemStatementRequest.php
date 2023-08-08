<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetItemStatement;

/**
 * @license GPL-2.0-or-later
 */
class GetItemStatementRequest {

	private string $itemId;
	private string $statementId;

	public function __construct( string $itemId, string $statementId ) {
		$this->itemId = $itemId;
		$this->statementId = $statementId;
	}

	public function getItemId(): string {
		return $this->itemId;
	}

	public function getStatementId(): string {
		return $this->statementId;
	}
}
