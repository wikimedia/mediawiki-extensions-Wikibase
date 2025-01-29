<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\GetStatement;

use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\StatementIdRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\UseCaseRequest;

/**
 * @license GPL-2.0-or-later
 */
class GetStatementRequest implements UseCaseRequest, StatementIdRequest {

	private string $statementId;

	public function __construct( string $statementId ) {
		$this->statementId = $statementId;
	}

	public function getStatementId(): string {
		return $this->statementId;
	}
}
