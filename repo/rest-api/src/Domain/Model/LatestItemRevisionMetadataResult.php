<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Model;

use RuntimeException;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @license GPL-2.0-or-later
 */
final class LatestItemRevisionMetadataResult {

	private ?int $revisionId = null;
	private ?string $revisionTimestamp = null;
	private ?ItemId $redirectTarget = null;

	public static function concreteRevision( int $revisionId, string $revisionTimestamp ): self {
		$result = new self();
		$result->revisionId = $revisionId;
		$result->revisionTimestamp = $revisionTimestamp;

		return $result;
	}

	public static function redirect( ItemId $redirectTarget ): self {
		$result = new self();
		$result->redirectTarget = $redirectTarget;

		return $result;
	}

	public static function itemNotFound(): self {
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

	/**
	 * @throws RuntimeException if not a redirect result
	 */
	public function getRedirectTarget(): ItemId {
		if ( !$this->redirectTarget ) {
			throw new RuntimeException( __METHOD__ . ' called on a result object that does not contain a redirect.' );
		}

		return $this->redirectTarget;
	}

	public function itemExists(): bool {
		return $this->revisionId || $this->redirectTarget;
	}

	public function isRedirect(): bool {
		return isset( $this->redirectTarget );
	}

}
