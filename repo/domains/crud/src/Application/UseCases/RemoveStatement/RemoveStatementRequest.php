<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\RemoveStatement;

use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\EditMetadataRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\StatementIdRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\UseCaseRequest;

/**
 * @license GPL-2.0-or-later
 */
class RemoveStatementRequest implements UseCaseRequest, StatementIdRequest, EditMetadataRequest {

	private string $statementId;
	private array $editTags;
	private bool $isBot;
	private ?string $comment;
	private ?string $username;

	public function __construct(
		string $statementId,
		array $editTags,
		bool $isBot,
		?string $comment,
		?string $username
	) {
		$this->statementId = $statementId;
		$this->editTags = $editTags;
		$this->isBot = $isBot;
		$this->comment = $comment;
		$this->username = $username;
	}

	public function getStatementId(): string {
		return $this->statementId;
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

	public function hasUser(): bool {
		return $this->username !== null;
	}

	public function getUsername(): ?string {
		return $this->username;
	}

}
