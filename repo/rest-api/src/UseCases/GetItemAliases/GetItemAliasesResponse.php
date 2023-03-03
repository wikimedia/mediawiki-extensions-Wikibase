<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItemAliases;

use Wikibase\Repo\RestApi\Domain\ReadModel\Aliases;

/**
 * @license GPL-2.0-or-later
 */
class GetItemAliasesResponse {

	private Aliases $aliases;
	private string $lastModified;
	private int $revisionId;

	public function __construct( Aliases $aliases, string $lastModified, int $revisionId ) {
		$this->aliases = $aliases;
		$this->lastModified = $lastModified;
		$this->revisionId = $revisionId;
	}

	public function getAliases(): Aliases {
		return $this->aliases;
	}

	public function getLastModified(): string {
		return $this->lastModified;
	}

	public function getRevisionId(): int {
		return $this->revisionId;
	}

}
