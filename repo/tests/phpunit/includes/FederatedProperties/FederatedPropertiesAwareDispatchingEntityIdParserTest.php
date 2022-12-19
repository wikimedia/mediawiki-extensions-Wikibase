<?php
declare( strict_types=1 );

namespace Wikibase\Repo\Tests\FederatedProperties;

use PHPUnit\Framework\TestCase;
use Wikibase\DataAccess\ApiEntitySource;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataModel\Entity\DispatchingEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\Lib\FederatedProperties\FederatedPropertyId;
use Wikibase\Lib\SubEntityTypesMapper;
use Wikibase\Repo\FederatedProperties\BaseUriExtractor;
use Wikibase\Repo\FederatedProperties\FederatedPropertiesAwareDispatchingEntityIdParser;

/**
 * @covers FederatedPropertiesAwareDispatchingEntityIdParser
 * @group Wikibase
 * @license GPL-2.0-or-later
 */
class FederatedPropertiesAwareDispatchingEntityIdParserTest extends TestCase {

	public function testParserDelegatesToDispatchingEntityIdParser_whenIdIsNotAFederatedProperty(): void {
		$dispatchingParser = $this->createMock( DispatchingEntityIdParser::class );
		$idSerialization = 'P12';
		$dispatchingParser->expects( $this->atLeastOnce() )
			->method( 'parse' )
			->with( $idSerialization )
			->willReturn( $this->createMock( EntityId::class ) );
		$fpParser = $this->getFederatedPropertiesAwareDispatchingEntityIdParser( $dispatchingParser );

		$fpParser->parse( $idSerialization );
	}

	public function testParserReturnsFederatedPropertyId(): void {
		$dispatchingParser = $this->createMock( DispatchingEntityIdParser::class );
		$remoteIdSerialization = 'P12';
		$idSerialization = "http://www.wikidata.org/entity/$remoteIdSerialization";
		$dispatchingParser->expects( $this->never() )->method( 'parse' );
		$fpParser = $this->getFederatedPropertiesAwareDispatchingEntityIdParser( $dispatchingParser );

		$entityId = $fpParser->parse( $idSerialization );

		$this->assertInstanceOf( FederatedPropertyId::class, $entityId );

		/** @var $entityId FederatedPropertyId */
		$this->assertSame( $remoteIdSerialization, $entityId->getRemoteIdSerialization() );
	}

	public function testParserThrowsWhenConceptURIisNotKnown(): void {
		$dispatchingParser = $this->createMock( DispatchingEntityIdParser::class );
		$idSerialization = 'http://www.bladata.org/entity/P12';
		$dispatchingParser->expects( $this->never() )->method( 'parse' );
		$fpParser = $this->getFederatedPropertiesAwareDispatchingEntityIdParser( $dispatchingParser );
		$this->expectException( EntityIdParsingException::class );

		$fpParser->parse( $idSerialization );
	}

	public function testParserThrowsWhenConceptURIisKnownButIsNotAnAPIsource(): void {
		$dispatchingParser = $this->createMock( DispatchingEntityIdParser::class );
		$idSerialization = 'http://www.localhost.org/entity/P12';
		$dispatchingParser->expects( $this->never() )->method( 'parse' );
		$fpParser = $this->getFederatedPropertiesAwareDispatchingEntityIdParser( $dispatchingParser );
		$this->expectException( EntityIdParsingException::class );

		$fpParser->parse( $idSerialization );
	}

	private function getFederatedPropertiesAwareDispatchingEntityIdParser( $dispatchingParser
	): FederatedPropertiesAwareDispatchingEntityIdParser {
		$definitions = new EntitySourceDefinitions(
			[
				new DatabaseEntitySource(
					'local',
					false,
					[],
					'http://www.localhost.org/entity/',
					'',
					'',
					''
				),
				new ApiEntitySource(
					'wikidorta',
					[ 'property' ],
					'',
					'',
					'',
					''
				),
				new ApiEntitySource(
					'wikidata',
					[ 'property' ],
					'http://www.wikidata.org/entity/',
					'',
					'',
					''
				),
			],
			new SubEntityTypesMapper( [] )
		);
		$fpParser = new FederatedPropertiesAwareDispatchingEntityIdParser( $dispatchingParser, new BaseUriExtractor(), $definitions );

		return $fpParser;
	}
}
