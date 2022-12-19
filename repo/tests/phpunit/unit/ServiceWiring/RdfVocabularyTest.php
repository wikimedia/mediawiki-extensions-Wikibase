<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use HashConfig;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\SubEntityTypesMapper;
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
		$localEntitySource = new DatabaseEntitySource(
			'test-local',
			false,
			[],
			'https://local.test/',
			'tl',
			'l',
			''
		);
		$otherEntitySource = new DatabaseEntitySource(
			'test-other',
			'other',
			[],
			'https://other.test/',
			'to',
			'o',
			'o'
		);
		$this->mockService( 'WikibaseRepo.Settings',
			new SettingsArray( [
				'canonicalLanguageCodes' => [ 't1' => 'test-1' ],
				'pagePropertiesRdf' => $pagePropertyDefs,
				'rdfDataRightsUrl' => 'https://license.test/cc0',
			] ) );
		$this->mockService( 'WikibaseRepo.EntitySourceDefinitions',
			new EntitySourceDefinitions( [
				$localEntitySource,
				$otherEntitySource,
			], new SubEntityTypesMapper( [] ) ) );
		$this->mockService( 'WikibaseRepo.DataTypeDefinitions',
			new DataTypeDefinitions( [
				'PT:test' => [ 'rdf-uri' => 'https://rdf.test/Datatype' ],
			] ) );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getMainConfig' )
			->willReturn( new HashConfig( [
				'DummyLanguageCodes' => [ 'd1' => 'dummy-1' ],
			] ) );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getTitleFactory' );

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
		$testType = $testProperty->getDataTypeId();
		$this->assertSame( 'https://rdf.test/Datatype', $rdfVocabulary->getDataTypeURI( $testType ) );
	}

}
