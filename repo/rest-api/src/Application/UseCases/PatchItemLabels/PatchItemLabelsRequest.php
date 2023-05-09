<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchItemLabels;

/**
 * @license GPL-2.0-or-later
 */
class PatchItemLabelsRequest {

	private string $itemId;
	private array $patch;
	private array $editTags;
	private bool $isBot;
	private ?string $comment;

	public function __construct(
		string $itemId,
		array $patch,
		array $editTags,
		bool $isBot,
		?string $comment
	) {
		$this->itemId = $itemId;
		$this->patch = $patch;
		$this->editTags = $editTags;
		$this->isBot = $isBot;
		$this->comment = $comment;
	}

	public function getItemId(): string {
		return $this->itemId;
	}

	public function getPatch(): array {
		return $this->patch;
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
