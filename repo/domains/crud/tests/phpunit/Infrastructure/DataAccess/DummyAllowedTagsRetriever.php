<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Infrastructure\DataAccess;

use Wikibase\Repo\Domains\Crud\Domain\Services\TagsRetriever;
use Wikibase\Repo\Tests\Domains\Crud\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;

/**
 * Use this only as a filler when only a list of valid site IDs is required.
 *
 * @license GPL-2.0-or-later
 */
class DummyAllowedTagsRetriever implements TagsRetriever {
	public function getAllowedTags(): array {
		return TestValidatingRequestDeserializer::ALLOWED_TAGS;
	}
}
