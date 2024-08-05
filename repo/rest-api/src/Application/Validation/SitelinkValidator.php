<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Validation;

use Wikibase\DataModel\SiteLink;

/**
 * @license GPL-2.0-or-later
 */
interface SitelinkValidator {

	public const CODE_TITLE_MISSING = 'sitelink-validator-code-title-missing';
	public const CODE_EMPTY_TITLE = 'sitelink-validator-code-empty-title';
	public const CODE_INVALID_TITLE = 'sitelink-validator-code-invalid-title';
	public const CODE_INVALID_TITLE_TYPE = 'sitelink-validator-code-invalid-title-type';
	public const CODE_INVALID_BADGES_TYPE = 'sitelink-validator-code-invalid-badges-type';
	public const CODE_INVALID_BADGE = 'sitelink-validator-code-invalid-badge';
	public const CODE_BADGE_NOT_ALLOWED = 'sitelink-validator-code-badge-not-allowed';
	public const CODE_TITLE_NOT_FOUND = 'sitelink-validator-code-title-not-found';
	public const CODE_SITELINK_CONFLICT = 'sitelink-validator-code-sitelink-conflict';

	public const CONTEXT_BADGE = 'sitelink-validator-context-badge';
	public const CONTEXT_CONFLICTING_ITEM_ID = 'sitelink-validator-context-conflicting-item-id';
	public const CONTEXT_SITE_ID = 'sitelink-validator-context-site-id';

	/**
	 * @param string|null $itemId - null if validating a new item
	 */
	public function validate( ?string $itemId, string $siteId, array $sitelink ): ?ValidationError;

	public function getValidatedSitelink(): SiteLink;

}
