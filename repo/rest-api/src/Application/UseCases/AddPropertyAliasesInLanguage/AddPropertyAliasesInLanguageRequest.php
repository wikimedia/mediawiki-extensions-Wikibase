<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\AddPropertyAliasesInLanguage;

use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\EditMetadataRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\PropertyIdRequest;

/**
 * @license GPL-2.0-or-later
 */
class AddPropertyAliasesInLanguageRequest implements PropertyIdRequest, EditMetadataRequest {

	private string $propertyId;
	private string $languageCode;
	private array $aliases;
	private array $editTags;
	private bool $isBot;
	private ?string $comment;
	private ?string $username;

	public function __construct(
		string $propertyId,
		string $languageCode,
		array $aliases,
		array $editTags,
		bool $isBot,
		?string $comment,
		?string $username
	) {
		$this->propertyId = $propertyId;
		$this->languageCode = $languageCode;
		$this->aliases = $aliases;
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

	public function getAliasesInLanguage(): array {
		return $this->aliases;
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
