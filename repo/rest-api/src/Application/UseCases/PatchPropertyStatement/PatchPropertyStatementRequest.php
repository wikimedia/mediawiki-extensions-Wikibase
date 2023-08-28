<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyStatement;

/**
 * @license GPL-2.0-or-later
 */
class PatchPropertyStatementRequest {

	private string $propertyId;
	private string $statementId;
	private array $patch;
	private array $editTags;
	private bool $isBot;
	private ?string $comment;
	private ?string $username;

	public function __construct(
		string $propertyId,
		string $statementId,
		array $patch,
		array $editTags,
		bool $isBot,
		?string $comment,
		?string $username
	) {
		$this->propertyId = $propertyId;
		$this->statementId = $statementId;
		$this->patch = $patch;
		$this->editTags = $editTags;
		$this->isBot = $isBot;
		$this->comment = $comment;
		$this->username = $username;
	}

	public function getPropertyId(): string {
		return $this->propertyId;
	}

	public function getStatementId(): string {
		return $this->statementId;
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
