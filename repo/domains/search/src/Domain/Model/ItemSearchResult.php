<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Domain\Model;

use Wikibase\DataModel\Entity\ItemId;

/**
 * @license GPL-2.0-or-later
 */
class ItemSearchResult {

	private ItemId $itemId;
	private string $label;
	private string $description;

	public function __construct( ItemId $itemId, string $label, string $description ) {
		$this->itemId = $itemId;
		$this->label = $label;
		$this->description = $description;
	}

	public function getItemId(): ItemId {
		return $this->itemId;
	}

	public function getLabel(): string {
		return $this->label;
	}

	public function getDescription(): string {
		return $this->description;
	}
}
