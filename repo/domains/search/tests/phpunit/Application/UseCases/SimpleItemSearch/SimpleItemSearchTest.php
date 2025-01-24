<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Search\Application\UseCases\SimpleItemSearch;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\Domains\Search\Application\UseCases\SimpleItemSearch\SimpleItemSearch;

/**
 * @covers \Wikibase\Repo\Domains\Search\Application\UseCases\SimpleItemSearch\SimpleItemSearch
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SimpleItemSearchTest extends TestCase {

	public function testCanConstruct(): void {
		$this->assertInstanceOf( SimpleItemSearch::class, new SimpleItemSearch() );
	}

}
