<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\GetStatement;

use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Statement;

/**
 * @license GPL-2.0-or-later
 */
class GetStatementResponse {

	private Statement $statement;

	/**
	 * @var string timestamp in MediaWiki format 'YYYYMMDDhhmmss'
	 */
	private string $lastModified;

	private int $revisionId;

	public function __construct(
		Statement $statement,
		string $lastModified,
		int $revisionId
	) {

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
