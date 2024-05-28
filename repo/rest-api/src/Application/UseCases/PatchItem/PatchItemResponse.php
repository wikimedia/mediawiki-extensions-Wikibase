<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchItem;

use Wikibase\Repo\RestApi\Domain\ReadModel\Item;

/**
 * @license GPL-2.0-or-later
 */
class PatchItemResponse {

	private Item $item;
	private string $lastModified;
	private int $revisionId;

	public function __construct( Item $item, string $lastModified, int $revisionId ) {
		$this->item = $item;
		$this->lastModified = $lastModified;
		$this->revisionId = $revisionId;
	}

	public function getItem(): Item {
		return $this->item;
	}

	public function getLastModified(): string {
		return $this->lastModified;
	}

	public function getRevisionId(): int {
		return $this->revisionId;
	}

}
