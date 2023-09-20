<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCaseRequestValidation;

/**
 * @license GPL-2.0-or-later
 */
interface EditMetadataRequest {
	public function getUsername(): ?string;

	public function isBot(): bool;

	public function getComment(): ?string;

	public function getEditTags(): array;
}
