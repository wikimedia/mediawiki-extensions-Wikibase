<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Domain\ReadModel;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Domain\ReadModel\Description;
use Wikibase\Repo\RestApi\Domain\ReadModel\Descriptions;

/**
 * @covers \Wikibase\Repo\RestApi\Domain\ReadModel\Descriptions
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class DescriptionsTest extends TestCase {

	public function testConstructor(): void {
		$enDescription = new Description( 'en', 'staple food' );
		$deDescription = new Description( 'de', 'unterirdische Knollen der Kartoffel (Solanum tuberosum)' );
		$descriptions = new Descriptions( $enDescription, $deDescription );

		$this->assertSame( $enDescription, $descriptions['en'] );
		$this->assertSame( $deDescription, $descriptions['de'] );
	}

}
