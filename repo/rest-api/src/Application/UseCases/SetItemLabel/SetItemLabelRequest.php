<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\SetItemLabel;

/**
 * @license GPL-2.0-or-later
 */
class SetItemLabelRequest {

	private string $itemId;
	private string $languageCode;
	private string $label;
	private array $editTags;
	private bool $isBot;
	private ?string $comment;

	public function __construct(
		string $itemId,
		string $languageCode,
		string $label,
		array $editTags,
		bool $isBot,
		?string $comment
	) {

		$this->itemId = $itemId;
		$this->languageCode = $languageCode;
		$this->label = $label;
		$this->editTags = $editTags;
		$this->isBot = $isBot;
		$this->comment = $comment;
	}

	public function getItemId(): string {
		return $this->itemId;
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

}
