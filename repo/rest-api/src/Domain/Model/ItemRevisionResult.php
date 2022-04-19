<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Model;

use Wikibase\DataModel\Entity\ItemId;

/**
 * @license GPL-2.0-or-later
 */
final class ItemRevisionResult {

	/**
	 * @var ?ItemRevision
	 */
	private $revision;

	/**
	 * @var ?ItemId
	 */
	private $redirectTarget;

	public static function concreteRevision( ItemRevision $revision ): self {
		$result = new self();
		$result->revision = $revision;

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
	 * @throws \RuntimeException if not a concrete revision result
	 */
	public function getRevision(): ItemRevision {
		if ( !$this->revision ) {
			throw new \RuntimeException( __METHOD__ . ' called on a result object that does not contain a revision.' );
		}

		return $this->revision;
	}

	/**
	 * @throws \RuntimeException if not a redirect result
	 */
	public function getRedirectTarget(): ItemId {
		if ( !$this->redirectTarget ) {
			throw new \RuntimeException( __METHOD__ . ' called on a result object that does not contain a redirect.' );
		}

		return $this->redirectTarget;
	}

	public function itemExists(): bool {
		return $this->revision || $this->redirectTarget;
	}

	public function isRedirect(): bool {
		return isset( $this->redirectTarget );
	}

}
