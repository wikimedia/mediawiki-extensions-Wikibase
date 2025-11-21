<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL;

/**
 * @license GPL-2.0-or-later
 */
class QueryContext {

	public const KEY_MESSAGE = 'message';
	public const KEY_REDIRECTS = 'redirects';

	public const MESSAGE_REDIRECTS = 'For at least one Item, redirects have been resolved automatically';

	/** @var array<string,string> */
	public array $redirects = [];
}
