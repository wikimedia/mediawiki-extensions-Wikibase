<?php

declare( strict_types=1 );

namespace Wikibase\Repo\Tests\FederatedProperties\DataAccess;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\SubEntityTypesMapper;
use Wikibase\Repo\FederatedProperties\FederatedPropertiesEntitySourceDefinitionsConfigParser;

/**
 * @covers \Wikibase\Repo\FederatedProperties\FederatedPropertiesEntitySourceDefinitionsConfigParser
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 *
 * @author Tobias Andersson
 */
class FederatedPropertiesEntitySourceDefinitionsConfigParserTest extends TestCase {

	public function testThrowsExceptionWhenNoSourceDefinitionIsFoundForLocalEntityNamespace() {
		$nonDefaultEntitySourceName = $this->getDefaultEntitySource( [
			'item' => [ 'namespaceId' => 120, 'slot' => 'main' ],
			'property' => [ 'namespaceId' => 120, 'slot' => 'main' ]
		],
		'something else' );

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'No entity sources defined for "local"' );

		$defaultSettings = new SettingsArray( $this->defaultArraySettings );

		$parser = new FederatedPropertiesEntitySourceDefinitionsConfigParser( $defaultSettings );
		$subEntityTypesMapper = new SubEntityTypesMapper( [] );

		$parser->initializeDefaults(
			new EntitySourceDefinitions( [ $nonDefaultEntitySourceName ], $subEntityTypesMapper ),
			$subEntityTypesMapper
		);
	}

	/**
	 * @dataProvider entitySourceProvider
	 */
	public function testFederatedPropertiesInitializesDefaults(
		EntitySourceDefinitions $sourceDefinitions,
		SubEntityTypesMapper $subEntityTypesMapper,
		SettingsArray $settings,
		array $expectedEntitySourceArray
	) {
		$parser = new FederatedPropertiesEntitySourceDefinitionsConfigParser( $settings );
		$newSourceDefinitions = $parser->initializeDefaults( $sourceDefinitions, $subEntityTypesMapper );

		$propertySource = $this->getFedPropsSource( $newSourceDefinitions );

		$this->assertSame( 'fedprops', $propertySource->getSourceName() );
		$this->assertSame( 'http://www.wikidata.org/entity/', $propertySource->getConceptBaseUri() );
		$this->assertSame( 'fpwd', $propertySource->getRdfPredicateNamespacePrefix() );
		$this->assertSame( 'fpwd', $propertySource->getRdfNodeNamespacePrefix() );
		$this->assertSame( 'wikidata', $propertySource->getInterwikiPrefix() );
		$this->assertSame( [ 'property' => 120 ], $propertySource->getEntityNamespaceIds() );
		$this->assertSame( [ 'property' => 'main' ], $propertySource->getEntitySlotNames() );
		$this->assertSame( [ 'property' ], $propertySource->getEntityTypes() );

		foreach ( $expectedEntitySourceArray as $entityType => $expectedSource ) {
			$result = $newSourceDefinitions->getSourceForEntityType( $entityType );
			$this->assertEquals( $expectedSource, $result );
		}
	}

	private function getDefaultEntitySource( array $namespaceDefinition, $sourceName = 'local' ) {
		return new EntitySource(
			$sourceName,
			false,
			$namespaceDefinition,
			'http://localhost/entity',
			'wd',
			'wikidata',
			''
		);
	}

	public function entitySourceProvider() {
		$defaultLocal = $this->getDefaultEntitySource( [
			'item' => [ 'namespaceId' => 120, 'slot' => 'main' ],
			'property' => [ 'namespaceId' => 120, 'slot' => 'main' ]
		] );

		$mediainfoLocal = $this->getDefaultEntitySource( [
			'item' => [ 'namespaceId' => 120, 'slot' => 'main' ],
			'property' => [ 'namespaceId' => 120, 'slot' => 'main' ],
			'mediainfo' => [ 'namespaceId' => 123, 'slot' => 'main' ]
		] );

		$subEntityTypesMapper = new SubEntityTypesMapper( [] );

		$defaultSettings = new SettingsArray( $this->defaultArraySettings );

		return [
			'default' => [
				new EntitySourceDefinitions( [ $defaultLocal ], $subEntityTypesMapper ),
				$subEntityTypesMapper,
				$defaultSettings,
				[
					'item' => $this->getDefaultEntitySource( [ 'item' => [ 'namespaceId' => 120, 'slot' => 'main' ] ] )
				]
			],
			'defaultWithMediaInfo' => [
				new EntitySourceDefinitions( [ $mediainfoLocal ], $subEntityTypesMapper ),
				$subEntityTypesMapper,
				$defaultSettings,
				[
					'item' => $this->getDefaultEntitySource( [
						'item' => [ 'namespaceId' => 120, 'slot' => 'main' ],
						'mediainfo' => [ 'namespaceId' => 123, 'slot' => 'main' ]
					] ),
					'mediainfo' => $this->getDefaultEntitySource( [
						'item' => [ 'namespaceId' => 120, 'slot' => 'main' ],
						'mediainfo' => [ 'namespaceId' => 123, 'slot' => 'main' ]
					] )

				]
			],
		];
	}

	private $defaultArraySettings = [
		'federatedPropertiesEnabled' => true,
		'federatedPropertiesSourceScriptUrl' => 'https://www.wikidata.org/w/',
		'localEntitySourceName' => 'local'
	];

	private function getFedPropsSource( EntitySourceDefinitions $sourceDefinitions ): EntitySource {
		foreach ( $sourceDefinitions->getSources() as $source ) {
			if ( $source->getType() === EntitySource::TYPE_API && in_array( 'property', $source->getEntityTypes() ) ) {
				return $source;
			}
		}

		throw new \LogicException( 'No federated properties source defined.' );
	}

}
