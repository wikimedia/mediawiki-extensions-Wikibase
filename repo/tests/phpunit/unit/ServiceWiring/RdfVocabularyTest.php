<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use HashConfig;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\SettingsArray;
use Wikibase\Repo\Rdf\RdfVocabulary;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class RdfVocabularyTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$pagePropertyDefs = [
			'test-prop' => [ 'name' => 'tp', 'type' => 'integer' ],
		];
		$localEntitySource = new EntitySource(
			'test-local',
			false,
			[],
			'https://local.test/',
			'tl',
			'l',
			''
		);
		$otherEntitySource = new EntitySource(
			'test-other',
			'other',
			[],
			'https://other.test/',
			'to',
			'o',
			'o'
		);
		$this->serviceContainer->expects( $this->exactly( 4 ) )
			->method( 'get' )
			->willReturnCallback( function ( string $id ) use ( $pagePropertyDefs, $localEntitySource, $otherEntitySource ) {
				switch ( $id ) {
					case 'WikibaseRepo.Settings':
						return new SettingsArray( [
							'canonicalLanguageCodes' => [ 't1' => 'test-1', ],
							'pagePropertiesRdf' => $pagePropertyDefs,
							'rdfDataRightsUrl' => 'https://license.test/cc0',
						] );
					case 'WikibaseRepo.LocalEntitySource':
						return $localEntitySource;
					case 'WikibaseRepo.EntitySourceDefinitions':
						return new EntitySourceDefinitions( [
							$localEntitySource,
							$otherEntitySource,
						], new EntityTypeDefinitions( [] ) );
					case 'WikibaseRepo.DataTypeDefinitions':
						return new DataTypeDefinitions( [
							'PT:test' => [ 'rdf-uri' => 'https://rdf.test/Datatype' ],
						] );
					default:
						$this->fail( "Unexpected service $id" );
				}
			} );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getMainConfig' )
			->willReturn( new HashConfig( [
				'DummyLanguageCodes' => [ 'd1' => 'dummy-1', ],
			] ) );

		/** @var RdfVocabulary $rdfVocabulary */
		$rdfVocabulary = $this->getService( 'WikibaseRepo.RdfVocabulary' );

		$this->assertInstanceOf( RdfVocabulary::class, $rdfVocabulary );
		$this->assertSame( 'test-1', $rdfVocabulary->getCanonicalLanguageCode( 't1' ) );
		$this->assertSame( 'dummy-1', $rdfVocabulary->getCanonicalLanguageCode( 'd1' ) );
		$this->assertSame( 'xyz', $rdfVocabulary->getCanonicalLanguageCode( 'xyz' ) );
		$this->assertSame( $pagePropertyDefs, $rdfVocabulary->getPagePropertyDefs() );
		$this->assertSame( 'https://license.test/cc0', $rdfVocabulary->getLicenseUrl() );
		$this->assertSame( 'https://local.test/', $rdfVocabulary->getNamespaceURI( 'tl' ) );
		$this->assertSame( 'https://local.test/prop/statement/', $rdfVocabulary->getNamespaceURI( 'lps' ) );
		$this->assertSame( 'https://other.test/reference/', $rdfVocabulary->getNamespaceURI( 'oref' ) );
		$testProperty = new Property( null, null, 'test' );
		$this->assertSame( 'https://rdf.test/Datatype', $rdfVocabulary->getDataTypeURI( $testProperty ) );
	}

}
