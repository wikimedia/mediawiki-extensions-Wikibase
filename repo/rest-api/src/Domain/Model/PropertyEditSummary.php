<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Model;

use Wikibase\DataModel\Entity\Property;

/**
 * @license GPL-2.0-or-later
 */
class PropertyEditSummary implements EditSummary {

	private string $editAction;
	private ?string $userComment;
	private Property $patchedProperty;
	private Property $originalProperty;

	public function __construct( string $editAction, ?string $userComment, Property $originalProperty, Property $patchedProperty ) {
		$this->editAction = $editAction;
		$this->userComment = $userComment;
		$this->originalProperty = $originalProperty;
		$this->patchedProperty = $patchedProperty;
	}

	public static function newPatchSummary( ?string $userComment, Property $originalProperty, Property $patchedProperty ): self {
		return new self( self::PATCH_ACTION, $userComment, $originalProperty, $patchedProperty );
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
