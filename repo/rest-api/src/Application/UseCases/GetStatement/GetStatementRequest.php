<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetStatement;

/**
 * @license GPL-2.0-or-later
 */
class GetStatementRequest {

	private string $statementId;

	public function __construct( string $statementId ) {
		$this->statementId = $statementId;
	}

	public function getStatementId(): string {
		return $this->statementId;
	}
}
