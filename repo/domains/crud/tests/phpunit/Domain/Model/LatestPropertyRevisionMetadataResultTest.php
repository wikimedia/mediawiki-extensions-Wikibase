<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Domain\Model;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\LatestPropertyRevisionMetadataResult;

/**
 * @covers \Wikibase\Repo\Domains\Crud\Domain\ReadModel\LatestPropertyRevisionMetadataResult
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class LatestPropertyRevisionMetadataResultTest extends TestCase {

	public function testGetRevisionId(): void {
		$revisionId = 123;
		$result = LatestPropertyRevisionMetadataResult::concreteRevision( $revisionId, '20220101001122' );
		$this->assertSame( $revisionId, $result->getRevisionId() );
	}

	public function testGetRevisionTimestamp(): void {
		$revisionTimestamp = '20220101001122';
		$result = LatestPropertyRevisionMetadataResult::concreteRevision( 123, $revisionTimestamp );
		$this->assertSame( $revisionTimestamp, $result->getRevisionTimestamp() );
	}
}
