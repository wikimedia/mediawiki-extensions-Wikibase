<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItem;

use Wikibase\Repo\RestApi\Domain\Model\ErrorReporter;

/**
 * @license GPL-2.0-or-later
 */
class GetItemResult {

	/**
	 * @var ErrorReporter
	 */
	private $error = null;

	/**
	 * @var array
	 */
	private $itemSerialization = null;

	/**
	 * @var string timestamp in MediaWiki format 'YYYYMMDDhhmmss'
	 */
	private $lastModified = null;

	/**
	 * @var int
	 */
	private $revisionId = null;

	public static function newSuccessResult( array $itemSerialization, string $lastModified, int $revisionId ): self {
		$result = new self();
		$result->itemSerialization = $itemSerialization;
		$result->lastModified = $lastModified;
		$result->revisionId = $revisionId;

		return $result;
	}

	public static function newFailureResult( ErrorReporter $error ): self {
		$result = new self();
		$result->error = $error;

		return $result;
	}

	public function isSuccessful(): bool {
		return $this->error === null;
	}

	public function getError(): ?ErrorReporter {
		return $this->error;
	}

	public function getItem(): ?array {
		return $this->itemSerialization;
	}

	public function getLastModified(): ?string {
		return $this->lastModified;
	}

	public function getRevisionId(): ?int {
		return $this->revisionId;
	}
}
