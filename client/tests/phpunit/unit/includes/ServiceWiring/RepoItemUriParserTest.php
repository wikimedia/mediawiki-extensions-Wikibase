<?php
declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class RepoItemUriParserTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService(
			'WikibaseClient.ItemSource',
			$this->createMock( DatabaseEntitySource::class )
		);

		$this->assertInstanceOf(
			EntityIdParser::class,
			$this->getService( 'WikibaseClient.RepoItemUriParser' )
		);
	}

	public function testThrowsForBaseUriMismatch(): void {
		$this->mockService(
			'WikibaseClient.ItemSource',
			new DatabaseEntitySource(
				'test',
				false,
				[],
				'http://test.test/item/',
				'',
				'',
				''
			)
		);

		/** @var EntityIdParser $parser */
		$parser = $this->getService( 'WikibaseClient.RepoItemUriParser' );

		$this->expectException( EntityIdParsingException::class );
		$this->expectExceptionMessage( 'Missing expected prefix' );

		$parser->parse( 'http://some.other/uri-prefix/Q123456' );
	}

}
