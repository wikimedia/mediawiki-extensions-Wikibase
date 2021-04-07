<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Services\EntityId\SuffixEntityIdParser;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ItemUrlParserTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService(
			'WikibaseRepo.ItemVocabularyBaseUri',
			'http://test.test/items'
		);

		$this->assertInstanceOf(
			SuffixEntityIdParser::class,
			$this->getService( 'WikibaseRepo.ItemUrlParser' )
		);
	}

	public function testThrowsForBaseUriMismatch(): void {
		$this->mockService(
			'WikibaseRepo.ItemVocabularyBaseUri',
			'http://test.test/items'
		);

		/** @var SuffixEntityIdParser $parser */
		$parser = $this->getService( 'WikibaseRepo.ItemUrlParser' );

		$this->expectException( EntityIdParsingException::class );
		$this->expectExceptionMessage( 'Missing expected prefix' );

		$parser->parse( 'http://some.other/uri-prefix/Q123456' );
	}

}
