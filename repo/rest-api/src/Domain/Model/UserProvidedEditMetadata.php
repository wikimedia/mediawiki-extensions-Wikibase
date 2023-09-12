<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Model;

/**
 * @license GPL-2.0-or-later
 */
class UserProvidedEditMetadata {

	private User $user;
	private bool $isBot;
	private ?string $comment;
	private array $tags;

	public function __construct( User $user, bool $isBot, ?string $comment, array $tags ) {
		$this->user = $user;
		$this->isBot = $isBot;
		$this->comment = $comment;
		$this->tags = $tags;
	}

	public function getUser(): User {
		return $this->user;
	}

	public function isBot(): bool {
		return $this->isBot;
	}

	public function getComment(): ?string {
		return $this->comment;
	}

	public function getTags(): array {
		return $this->tags;
	}

}
