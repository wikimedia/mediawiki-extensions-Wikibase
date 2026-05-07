<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Infrastructure\DataAccess;

use MediaWiki\Language\Language;
use MediaWiki\Request\WebRequest;
use Wikibase\Repo\Api\EntitySearchHelper;

/**
 * @license GPL-2.0-or-later
 */
interface EntitySearchHelperFactory {

	public function newEntitySearchHelper( string $entityType, Language $language, WebRequest $request ): EntitySearchHelper;

}
