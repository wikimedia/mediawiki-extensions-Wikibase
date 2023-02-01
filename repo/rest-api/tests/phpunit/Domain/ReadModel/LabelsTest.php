<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Domain\ReadModel;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\RestApi\Domain\ReadModel\Label;
use Wikibase\Repo\RestApi\Domain\ReadModel\Labels;

/**
 * @covers \Wikibase\Repo\RestApi\Domain\ReadModel\Labels
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class LabelsTest extends TestCase {

	public function testConstructor(): void {
		$enLabel = new Label( 'en', 'potato' );
		$deLabel = new Label( 'de', 'Kartoffel' );
		$labels = new Labels( $enLabel, $deLabel );

		$this->assertSame( $enLabel, $labels['en'] );
		$this->assertSame( $deLabel, $labels['de'] );
	}

	public function testFromTermList(): void {
		$deText = 'Kartoffel';
		$enText = 'potato';
		$list = new TermList( [
			new Term( 'en', $enText ),
			new Term( 'de', $deText ),
		] );

		$labels = Labels::fromTermList( $list );

		$this->assertEquals( new Label( 'de', $deText ), $labels['de'] );
		$this->assertEquals( new Label( 'en', $enText ), $labels['en'] );
	}
}
