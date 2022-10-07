<?php
declare( strict_types=1 );
namespace Wikibase\Repo\FederatedProperties;

use PHPUnit\Framework\TestCase;
use Wikibase\DataAccess\ApiEntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataAccess\Tests\NewDatabaseEntitySource;
use Wikibase\Lib\SubEntityTypesMapper;
use const false;

/**
 * @group Wikibase
 * @license GPL-2.0-or-later
 * @covers \Wikibase\Repo\FederatedProperties\DefaultFederatedPropertiesEntitySourceAdder
 */
class DefaultFederatedPropertiesEntitySourceAdderTest extends TestCase {

	public function testAddDefaultIfRequired_doesNotChangeEntitySourcesIfFedPropsDisabled() {
		$adder = $this->getAdder( false, 'https://www.wikidata.org/w/' );
		$entitySourceDefinitions = $this->createStub( EntitySourceDefinitions::class );
		$resultingEntitySourceDefs = $adder->addDefaultIfRequired( $entitySourceDefinitions );
		$this->assertEquals( $entitySourceDefinitions, $resultingEntitySourceDefs );
	}

	public function testAddDefaultIfRequired_doesNotChangeEntitySourcesIfSourceScriptUrlNotDefault() {
			$adder = $this->getAdder( true, 'https://www.NOTwikidata.org/w/' );
			$entitySourceDefinitions = $this->createStub( EntitySourceDefinitions::class );
			$resultingEntitySourceDefs = $adder->addDefaultIfRequired( $entitySourceDefinitions );
			$this->assertEquals( $entitySourceDefinitions, $resultingEntitySourceDefs );
	}

	public function testAddDefaultIfRequired_AddsFederatedSourceWhenEnableAndSourceScriptUrlMatches() {
		$adder = $this->getAdder( true, 'https://www.wikidata.org/w/' );
		$localSource = NewDatabaseEntitySource::havingName( 'thislocalone' )->withEntityNamespaceIdsAndSlots( [] )->build();
		$entitySourceDefinitions = new EntitySourceDefinitions( [ $localSource ], $this->createStub( SubEntityTypesMapper::class ) );
		$resultingEntitySourceDefs = $adder->addDefaultIfRequired( $entitySourceDefinitions );
		$this->assertInstanceOf( ApiEntitySource::class, $resultingEntitySourceDefs->getApiSourceForEntityType( 'property' ) );
	}

	private function getAdder( bool $fedPropsEnabled, $sourceScriptUrl ): DefaultFederatedPropertiesEntitySourceAdder {
		return new DefaultFederatedPropertiesEntitySourceAdder(
			$fedPropsEnabled, $sourceScriptUrl,
			$this->createStub( SubEntityTypesMapper::class )
		);
	}
}
