<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Domain\ReadModel;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
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

	public function testFromTermList(): void {
		$deText = 'unterirdische Knollen der Kartoffel (Solanum tuberosum)';
		$enText = 'staple food';
		$list = new TermList( [
			new Term( 'en', $enText ),
			new Term( 'de', $deText ),
		] );

		$descriptions = Descriptions::fromTermList( $list );

		$this->assertEquals( new Description( 'de', $deText ), $descriptions['de'] );
		$this->assertEquals( new Description( 'en', $enText ), $descriptions['en'] );
	}

}
