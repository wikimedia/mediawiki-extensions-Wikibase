<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Model;

use Wikibase\DataModel\Entity\Item;

/**
 * @license GPL-2.0-or-later
 */
class PatchItemEditSummary implements EditSummary {

	private string $editAction;
	private ?string $userComment;
	private Item $originalItem;
	private Item $patchedItem;

	private function __construct( ?string $userComment, Item $originalItem, Item $patchedItem ) {
		$this->editAction = self::PATCH_ACTION;
		$this->userComment = $userComment;
		$this->originalItem = $originalItem;
		$this->patchedItem = $patchedItem;
	}

	public static function newSummary( ?string $userComment, Item $originalItem, Item $patchedItem ): self {
		return new self( $userComment, $originalItem, $patchedItem );
	}

	public function getEditAction(): string {
		return $this->editAction;
	}

	public function getUserComment(): ?string {
		return $this->userComment;
	}

	public function getOriginalItem(): Item {
		return $this->originalItem;
	}

	public function getPatchedItem(): Item {
		return $this->patchedItem;
	}

}
