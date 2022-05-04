<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItemStatement;

/**
 * @license GPL-2.0-or-later
 */
class GetItemStatementSuccessResponse {

	private $serializedStatement;

	/**
	 * @var string timestamp in MediaWiki format 'YYYYMMDDhhmmss'
	 */
	private $lastModified;

	private $revisionId;

	public function __construct(
		array $serializedStatement,
		string $lastModified,
		int $revisionId
	) {

		$this->serializedStatement = $serializedStatement;
		$this->lastModified = $lastModified;
		$this->revisionId = $revisionId;
	}

	public function getSerializedStatement(): array {
		return $this->serializedStatement;
	}

	public function getLastModified(): string {
		return $this->lastModified;
	}

	public function getRevisionId(): int {
		return $this->revisionId;
	}

}
