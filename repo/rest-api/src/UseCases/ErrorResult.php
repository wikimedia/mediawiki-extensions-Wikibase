<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases;

/**
 * @license GPL-2.0-or-later
 */
interface ErrorResult {
	public function getCode(): string;

	public function getMessage(): string;
}
