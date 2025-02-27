<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Domain\Model;

use Wikibase\DataModel\Entity\ItemId;

/**
 * @license GPL-2.0-or-later
 */
class ItemSearchResult {

	private ItemId $itemId;
	private ?Label $label;
	private ?Description $description;

	public function __construct( ItemId $itemId, ?Label $label, ?Description $description ) {
		$this->itemId = $itemId;
		$this->label = $label;
		$this->description = $description;
	}

	public function getItemId(): ItemId {
		return $this->itemId;
	}

	public function getLabel(): ?Label {
		return $this->label;
	}

	public function getDescription(): ?Description {
		return $this->description;
	}
}
