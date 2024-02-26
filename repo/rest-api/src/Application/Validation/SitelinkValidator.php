<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Validation;

use Wikibase\DataModel\SiteLink;

/**
 * @license GPL-2.0-or-later
 */
interface SitelinkValidator {

	public const CODE_TITLE_MISSING = 'title-missing';
	public const CODE_EMPTY_TITLE = 'empty-title';
	public const CODE_INVALID_TITLE = 'invalid-title';
	public const CODE_INVALID_TITLE_TYPE = 'invalid-title-type';

	public const CODE_INVALID_BADGES_TYPE = 'invalid-badges-type';
	public const CODE_INVALID_BADGE = 'invalid-badge';
	public const CODE_BADGE_NOT_ALLOWED = 'badge-not-allowed';
	public const CODE_TITLE_NOT_FOUND = 'title-not-found';
	public const CODE_SITELINK_CONFLICT = 'sitelink-conflict';

	public const CONTEXT_BADGE = 'badge';
	public const CONTEXT_CONFLICT_ITEM_ID = 'conflict_item_id';

	public function validate( string $itemId, string $siteId, array $sitelink ): ?ValidationError;

	public function getValidatedSitelink(): SiteLink;

}
