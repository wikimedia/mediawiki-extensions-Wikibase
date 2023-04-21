<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\SetItemDescription;

/**
 * @license GPL-2.0-or-later
 */
class SetItemDescriptionRequest {

	private string $itemId;
	private string $languageCode;
	private string $description;
	private array $editTags;
	private bool $isBot;
	private ?string $comment;
	private ?string $username;

	public function __construct(
		string $itemId,
		string $languageCode,
		string $description,
		array $editTags,
		bool $isBot,
		?string $comment,
		?string $username
	) {
		$this->itemId = $itemId;
		$this->languageCode = $languageCode;
		$this->description = $description;
		$this->editTags = $editTags;
		$this->isBot = $isBot;
		$this->comment = $comment;
		$this->username = $username;
	}

	public function getItemId(): string {
		return $this->itemId;
	}

	public function getLanguageCode(): string {
		return $this->languageCode;
	}

	public function getDescription(): string {
		return $this->description;
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
