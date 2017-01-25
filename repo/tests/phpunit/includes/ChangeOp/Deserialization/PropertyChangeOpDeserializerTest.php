<?php

namespace Wikibase\Repo\Tests\ChangeOp\Deserialization;

use Wikibase\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializerFactory;
use Wikibase\Repo\ChangeOp\Deserialization\PropertyChangeOpDeserializer;

/**
 * @covers Wikibase\Repo\ChangeOpDeserializers\PropertyChangeOpDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 */
class PropertyChangeOpDeserializerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return ChangeOpDeserializerFactory
	 */
	private function getChangeOpDeserializerFactory() {
		return $this->getMockBuilder( ChangeOpDeserializerFactory::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCreateEntityChangeOpReturnsChangeOpInstance() {
		$deserializer = new PropertyChangeOpDeserializer( $this->getChangeOpDeserializerFactory() );

		$this->assertInstanceOf( ChangeOp::class, $deserializer->createEntityChangeOp( [] ) );
	}

	/**
	 * @param string[] $methods
	 *
	 * @return ChangeOpDeserializerFactory
	 */
	private function getChangeOpDeserializerFactoryCallingMethods( $methods ) {
		$dummyDeserializer = $this->getMock( ChangeOpDeserializer::class );
		$dummyDeserializer->method( $this->anything() )
			->will( $this->returnValue( $this->getMock( ChangeOp::class ) ) );

		$factory = $this->getMockBuilder( ChangeOpDeserializerFactory::class )
			->disableOriginalConstructor()
			->getMock();

		foreach ( $methods as $method ) {
			$factory->expects( $this->atLeastOnce() )
				->method( $method )
				->will( $this->returnValue(
					$dummyDeserializer
				) );
		}

		return $factory;
	}

	public function provideChangeRequestsWithPropertyRelevantFields() {
		return [
			'labels' => [
				[ 'labels' => [ 'label-change-data' ] ],
				[ 'getLabelsChangeOpDeserializer' ]
			],
			'descriptions' => [
				[ 'descriptions' => [ 'description-change-data' ] ],
				[ 'getDescriptionsChangeOpDeserializer' ]
			],
			'aliases' => [
				[ 'aliases' => [ 'alias-change-data' ] ],
				[ 'getAliasesChangeOpDeserializer' ]
			],
			'statements' => [
				[ 'claims' => [ 'statement-change-data' ] ],
				[ 'getClaimsChangeOpDeserializer' ]
			],
			'labels and descriptions' => [
				[ 'labels' => [ 'label-change-data' ], 'descriptions' => [ 'description-change-data' ] ],
				[ 'getLabelsChangeOpDeserializer', 'getDescriptionsChangeOpDeserializer' ]
			],
			'all property fields' => [
				[
					'labels' => [ 'label-change-data' ],
					'descriptions' => [ 'description-change-data' ],
					'aliases' => [ 'alias-change-data' ],
					'claims' => [ 'statement-change-data' ],
				],
				[
					'getLabelsChangeOpDeserializer',
					'getDescriptionsChangeOpDeserializer',
					'getAliasesChangeOpDeserializer',
					'getClaimsChangeOpDeserializer'
				]
			],
		];
	}

	/**
	 * @dataProvider provideChangeRequestsWithPropertyRelevantFields
	 */
	public function testGivenPropertyRelevantFieldsInChangeRequest_deserializerForFieldsAreUsed(
		array $changeRequest,
		array $expectedMethods
	) {
		$deserializer = new PropertyChangeOpDeserializer(
			$this->getChangeOpDeserializerFactoryCallingMethods( $expectedMethods )
		);

		$deserializer->createEntityChangeOp( $changeRequest );
	}

	public function testGivenSitelinkFieldInChangeRequest_createEntityChangeOpThrowsException() {
		$deserializer = new PropertyChangeOpDeserializer( $this->getChangeOpDeserializerFactory() );

		$this->setExpectedException( ChangeOpDeserializationException::class );

		$deserializer->createEntityChangeOp( [ 'sitelinks' => [ 'site-link-change-data' ] ] );
	}

}
