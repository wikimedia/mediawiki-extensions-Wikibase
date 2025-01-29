<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\ReplaceStatement;

use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Statement;

/**
 * @license GPL-2.0-or-later
 */
class ReplaceStatementResponse {

	private Statement $statement;
	private string $lastModified;
	private int $revisionId;

	public function __construct( Statement $statement, string $lastModified, int $revisionId ) {
		$this->statement = $statement;
		$this->lastModified = $lastModified;
		$this->revisionId = $revisionId;
	}

	public function getStatement(): Statement {
		return $this->statement;
	}

	public function getLastModified(): string {
		return $this->lastModified;
	}

	public function getRevisionId(): int {
		return $this->revisionId;
	}
}
