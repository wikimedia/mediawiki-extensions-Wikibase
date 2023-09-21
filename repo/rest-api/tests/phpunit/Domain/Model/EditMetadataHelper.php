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
				TestCase::assertSame( $editTags, $editMetadata->getTags() );
				TestCase::assertSame( $isBot, $editMetadata->isBot() );
				TestCase::assertSame( $comment, $editMetadata->getSummary()->getUserComment() );
				TestCase::assertSame( $editAction, $editMetadata->getSummary()->getEditAction() );
				return true;
			}
		);
	}

}
