<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItem;

use Wikibase\Repo\RestApi\Domain\ReadModel\ItemData;

/**
 * @license GPL-2.0-or-later
 */
class GetItemResponse {

	private ItemData $itemData;

	/**
	 * @var string timestamp in MediaWiki format 'YYYYMMDDhhmmss'
	 */
	private string $lastModified;

	private int $revisionId;

	public function __construct( ItemData $itemData, string $lastModified, int $revisionId ) {
		$this->itemData = $itemData;
		$this->lastModified = $lastModified;
		$this->revisionId = $revisionId;
	}

	public function getItemData(): ItemData {
		return $this->itemData;
	}

	public function getLastModified(): string {
		return $this->lastModified;
	}

	public function getRevisionId(): int {
		return $this->revisionId;
	}
}
