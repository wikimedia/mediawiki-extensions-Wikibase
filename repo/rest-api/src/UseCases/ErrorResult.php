<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases;

/**
 * @license GPL-2.0-or-later
 */
interface ErrorResult {
	public const INVALID_ITEM_ID = 'invalid-item-id';
	public const ITEM_NOT_FOUND = 'item-not-found';
	public const UNEXPECTED_ERROR = 'unexpected-error';

	public function getCode(): string;

	public function getMessage(): string;
}
