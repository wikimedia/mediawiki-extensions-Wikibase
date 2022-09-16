<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Domain\Model;

use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;

/**
 * @license GPL-2.0-or-later
 */
trait EditMetadataHelper {

	private function expectEquivalentMetadata(
		array $editTags,
		bool $isBot,
		?string $comment,
		string $editAction
	): Callback {
		return TestCase::callback(
			function( EditMetadata $editMetadata ) use ( $editTags, $isBot, $comment, $editAction ): bool {
				TestCase::assertSame( $editMetadata->getTags(), $editTags );
				TestCase::assertSame( $editMetadata->isBot(), $isBot );
				TestCase::assertSame( $editMetadata->getSummary()->getUserComment(), $comment );
				TestCase::assertSame( $editMetadata->getSummary()->getEditAction(), $editAction );
				return true;
			}
		);
	}
}
