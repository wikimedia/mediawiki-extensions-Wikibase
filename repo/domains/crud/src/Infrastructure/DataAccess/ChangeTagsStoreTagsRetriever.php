<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess;

use MediaWiki\ChangeTags\ChangeTagsStore;
use Wikibase\Repo\Domains\Crud\Domain\Services\TagsRetriever;

/**
 * @license GPL-2.0-or-later
 */
class ChangeTagsStoreTagsRetriever implements TagsRetriever {

	public function __construct( private readonly ChangeTagsStore $changeTagsStore ) {
	}

	public function getAllowedTags(): array {
		return $this->changeTagsStore->listExplicitlyDefinedTags();
	}
}
