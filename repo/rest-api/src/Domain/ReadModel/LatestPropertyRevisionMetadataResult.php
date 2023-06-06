<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\ReadModel;

use RuntimeException;

/**
 * @license GPL-2.0-or-later
 */
final class LatestPropertyRevisionMetadataResult {

	private ?int $revisionId = null;
	private ?string $revisionTimestamp = null;

	public static function concreteRevision( int $revisionId, string $revisionTimestamp ): self {
		$result = new self();
		$result->revisionId = $revisionId;
		$result->revisionTimestamp = $revisionTimestamp;

		return $result;
	}

	public static function propertyNotFound(): self {
		return new self();
	}

	/**
	 * @throws RuntimeException if not a concrete revision result
	 */
	public function getRevisionId(): int {
		if ( !$this->revisionId ) {
			throw new RuntimeException( __METHOD__ . ' called on a result object that does not contain a revision.' );
		}

		return $this->revisionId;
	}

	/**
	 * @throws RuntimeException if not a concrete revision result
	 */
	public function getRevisionTimestamp(): string {
		if ( !$this->revisionTimestamp ) {
			throw new RuntimeException( __METHOD__ . ' called on a result object that does not contain a revision.' );
		}

		return $this->revisionTimestamp;
	}

	public function propertyExists(): bool {
		return isset( $this->revisionId );
	}

}
