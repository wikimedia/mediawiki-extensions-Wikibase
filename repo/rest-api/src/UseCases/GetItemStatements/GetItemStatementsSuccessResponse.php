<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItemStatements;

use Wikibase\DataModel\Statement\StatementList;

/**
 * @license GPL-2.0-or-later
 */
class GetItemStatementsSuccessResponse {

	private $statements;

	/**
	 * @var string timestamp in MediaWiki format 'YYYYMMDDhhmmss'
	 */
	private $lastModified;

	private $revisionId;

	public function __construct( StatementList $serializedStatements, string $lastModified, int $revisionId ) {
		$this->statements = $serializedStatements;
		$this->lastModified = $lastModified;
		$this->revisionId = $revisionId;
	}

	public function getStatements(): StatementList {
		return $this->statements;
	}

	public function getLastModified(): string {
		return $this->lastModified;
	}

	public function getRevisionId(): int {
		return $this->revisionId;
	}

}
