<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetItem;

use Wikibase\Repo\RestApi\Domain\ReadModel\ItemParts;

/**
 * @license GPL-2.0-or-later
 */
class GetItemResponse {

	private ItemParts $itemParts;

	/**
	 * @var string timestamp in MediaWiki format 'YYYYMMDDhhmmss'
	 */
	private string $lastModified;

	private int $revisionId;

	public function __construct( ItemParts $itemParts, string $lastModified, int $revisionId ) {
		$this->itemParts = $itemParts;
		$this->lastModified = $lastModified;
		$this->revisionId = $revisionId;
	}

	public function getItemParts(): ItemParts {
		return $this->itemParts;
	}

	public function getLastModified(): string {
		return $this->lastModified;
	}

	public function getRevisionId(): int {
		return $this->revisionId;
	}
}
