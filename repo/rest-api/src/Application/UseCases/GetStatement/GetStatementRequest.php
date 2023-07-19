<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetStatement;

/**
 * @license GPL-2.0-or-later
 */
class GetStatementRequest {

	private string $statementId;
	private ?string $subjectId;

	public function __construct( string $statementId, string $subjectId = null ) {
		$this->statementId = $statementId;
		$this->subjectId = $subjectId;
	}

	public function getStatementId(): string {
		return $this->statementId;
	}

	public function getSubjectId(): ?string {
		return $this->subjectId;
	}
}
