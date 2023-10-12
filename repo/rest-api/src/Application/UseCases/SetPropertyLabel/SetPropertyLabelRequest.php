<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\SetPropertyLabel;

use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\EditMetadataRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\PropertyLabelEditRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\UseCaseRequest;

/**
 * @license GPL-2.0-or-later
 */
class SetPropertyLabelRequest implements UseCaseRequest, PropertyLabelEditRequest, EditMetadataRequest {

	private string $propertyId;
	private string $languageCode;
	private string $label;
	private array $editTags;
	private bool $isBot;
	private ?string $comment;
	private ?string $username;

	public function __construct(
		string $propertyId,
		string $languageCode,
		string $label,
		array $editTags,
		bool $isBot,
		?string $comment,
		?string $username
	) {
		$this->propertyId = $propertyId;
		$this->languageCode = $languageCode;
		$this->label = trim( $label );
		$this->editTags = $editTags;
		$this->isBot = $isBot;
		$this->comment = $comment;
		$this->username = $username;
	}

	public function getPropertyId(): string {
		return $this->propertyId;
	}

	public function getLanguageCode(): string {
		return $this->languageCode;
	}

	public function getLabel(): string {
		return $this->label;
	}

	public function getEditTags(): array {
		return $this->editTags;
	}

	public function isBot(): bool {
		return $this->isBot;
	}

	public function getComment(): ?string {
		return $this->comment;
	}

	public function getUsername(): ?string {
		return $this->username;
	}

}
