<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL;

/**
 * @license GPL-2.0-or-later
 */
class QueryContext {

	public const KEY_MESSAGE = 'message';
	public const KEY_REDIRECTS = 'redirects';
	public const KEY_MISSING_ITEMS = 'missingItems';

	public const MESSAGE_REDIRECTS = 'For at least one Item, redirects have been resolved automatically';
	public const MESSAGE_MISSING_ITEMS = 'Item(s) %s no longer exists.';

	/** @var array<string,string> */
	public array $redirects = [];

	/** @var string[] */
	public array $missingItemIds = [];
}
