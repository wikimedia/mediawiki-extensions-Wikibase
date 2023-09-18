<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchItemLabels;

use Wikibase\Repo\RestApi\Application\UseCases\EditMetadataRequest;
use Wikibase\Repo\RestApi\Application\UseCases\ItemIdRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PatchRequest;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseRequest;

/**
 * @license GPL-2.0-or-later
 */
class PatchItemLabelsRequest implements UseCaseRequest, ItemIdRequest, PatchRequest, EditMetadataRequest {

	private string $itemId;
	private array $patch;
	private array $editTags;
	private bool $isBot;
	private ?string $comment;
	private ?string $username;

	public function __construct(
		string $itemId,
		array $patch,
		array $editTags,
		bool $isBot,
		?string $comment,
		?string $username
	) {
		$this->itemId = $itemId;
		$this->patch = $patch;
		$this->editTags = $editTags;
		$this->isBot = $isBot;
		$this->comment = $comment;
		$this->username = $username;
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

	public function getUsername(): ?string {
		return $this->username;
	}

}