<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetStatement;

/**
 * @license GPL-2.0-or-later
 */
class GetStatementRequest {

	private string $statementId;
	private ?string $entityId;

	public function __construct( string $statementId, string $entityId = null ) {
		$this->statementId = $statementId;
		$this->entityId = $entityId;
	}

	public function getStatementId(): string {
		return $this->statementId;
	}

	public function getEntityId(): ?string {
		return $this->entityId;
	}
}
