<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Model;

use Wikibase\DataModel\Entity\Property;

/**
 * @license GPL-2.0-or-later
 */
class PatchPropertyEditSummary implements EditSummary {

	private string $editAction;
	private ?string $userComment;
	private Property $patchedProperty;
	private Property $originalProperty;

	public function __construct( ?string $userComment, Property $originalProperty, Property $patchedProperty ) {
		$this->editAction = self::PATCH_ACTION;
		$this->userComment = $userComment;
		$this->originalProperty = $originalProperty;
		$this->patchedProperty = $patchedProperty;
	}

	public static function newSummary( ?string $userComment, Property $originalProperty, Property $patchedProperty ): self {
		return new self( $userComment, $originalProperty, $patchedProperty );
	}

	public function getEditAction(): string {
		return $this->editAction;
	}

	public function getUserComment(): ?string {
		return $this->userComment;
	}

	public function getOriginalProperty(): Property {
		return $this->originalProperty;
	}

	public function getPatchedProperty(): Property {
		return $this->patchedProperty;
	}

}
