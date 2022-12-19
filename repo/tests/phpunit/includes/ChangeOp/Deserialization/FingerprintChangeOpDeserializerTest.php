<?php

namespace Wikibase\Repo\Tests\ChangeOp\Deserialization;

use Wikibase\Repo\ChangeOp\ChangeOpFingerprint;
use Wikibase\Repo\ChangeOp\Deserialization\AliasesChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;
use Wikibase\Repo\ChangeOp\Deserialization\DescriptionsChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\Deserialization\FingerprintChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\Deserialization\LabelsChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\FingerprintChangeOpFactory;
use Wikibase\Repo\ChangeOp\NullChangeOp;
use Wikibase\Repo\Tests\ChangeOp\ChangeOpTestMockProvider;

/**
 * @covers \Wikibase\Repo\ChangeOp\Deserialization\FingerprintChangeOpDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class FingerprintChangeOpDeserializerTest extends \PHPUnit\Framework\TestCase {

	/** @var LabelsChangeOpDeserializer */
	private $labelsChangeOpDeserializerMock;

	/** @var DescriptionsChangeOpDeserializer */
	private $descriptionsChangeOpDeserializerMock;

	/** @var AliasesChangeOpDeserializer */
	private $aliasesChangeOpDeserializerMock;

	protected function setUp(): void {
		$createNullChangeOpCallback = function ( $changeRequest ) {
			return new NullChangeOp();
		};

		$this->labelsChangeOpDeserializerMock = $this->createMock( LabelsChangeOpDeserializer::class );
		$this->labelsChangeOpDeserializerMock
			->method( 'createEntityChangeOp' )
			->willReturnCallback( $createNullChangeOpCallback );

		$this->descriptionsChangeOpDeserializerMock = $this->createMock( DescriptionsChangeOpDeserializer::class );
		$this->descriptionsChangeOpDeserializerMock
			->method( 'createEntityChangeOp' )
			->willReturnCallback( $createNullChangeOpCallback );

		$this->aliasesChangeOpDeserializerMock = $this->createMock( AliasesChangeOpDeserializer::class );
		$this->aliasesChangeOpDeserializerMock
			->method( 'createEntityChangeOp' )
			->willReturnCallback( $createNullChangeOpCallback );
	}

	public function provideSubjectTestData() {
		return [

			'no fingerprint changes' => [
				'changeRequest' => [ 'claims' => [], 'siteLinks' => [] ],
				'expectedChangeOpType' => NullChangeOp::class,
				'expectedLengthOfChangeOps' => 0,
			],

			'label changes only' => [
				'changeRequest' => [ 'labels' => [ 'en' => [ 'language' => 'en', 'value' => 'foo' ] ] ],
				'expectedChangeOpType' => ChangeOpFingerprint::class,
				'expectedLengthOfChangeOps' => 1,
			],

			'description changes only' => [
				'changeRequest' => [ 'descriptions' => [ 'en' => [ 'language' => 'en', 'value' => 'foo' ] ] ],
				'expectedChangeOpType' => ChangeOpFingerprint::class,
				'expectedLengthOfChangeOps' => 1,
			],

			'aliases changes only' => [
				'changeRequest' => [ 'aliases' => [ 'en' => [ 'language' => 'en', 'value' => 'foo' ] ] ],
				'expectedChangeOpType' => ChangeOpFingerprint::class,
				'expectedLengthOfChangeOps' => 1,
			],

			'various changes' => [
				'changeRequest' => [
					'labels' => [ 'en' => [ 'language' => 'en', 'value' => 'foo' ] ],
					'descriptions' => [ 'en' => [ 'language' => 'en', 'value' => 'foo' ] ],
					'aliases' => [ 'en' => [ 'language' => 'en', 'value' => 'foo' ] ],
				],
				'expectedChangeOpType' => ChangeOpFingerprint::class,
				'expectedLengthOfChangeOps' => 3,
			],
		];
	}

	/**
	 * @dataProvider provideSubjectTestData
	 */
	public function testSubject( $changeRequest, $expectedChangeOpType, $expectedLengthOfChangeOps ) {
		$returnedChangeOp = $this->createEntityChangeOpWithTestSubject( $changeRequest );

		$this->assertInstanceOf( $expectedChangeOpType, $returnedChangeOp );

		if ( $returnedChangeOp instanceof ChangeOpFingerprint ) {
			$this->assertCount( $expectedLengthOfChangeOps, $returnedChangeOp->getChangeOps() );
		}
	}

	public function testSubject_whenLabelsDeserializerThrows_throws() {
		$this->expectException( ChangeOpDeserializationException::class );

		$this->labelsChangeOpDeserializerMock->method( 'createEntityChangeOp' )
			->willThrowException( $this->createMock( ChangeOpDeserializationException::class ) );

		$this->createEntityChangeOpWithTestSubject( [
			'labels' => [ 'en' => [ 'language' => 'en', 'value' => 'foo' ] ],
		] );
	}

	public function testSubject_whenDescriptionsDeserializerThrows_throws() {
		$this->expectException( ChangeOpDeserializationException::class );

		$this->descriptionsChangeOpDeserializerMock->method( 'createEntityChangeOp' )
			->willThrowException( $this->createMock( ChangeOpDeserializationException::class ) );

		$this->createEntityChangeOpWithTestSubject( [
			'descriptions' => [ 'en' => [ 'language' => 'en', 'value' => 'foo' ] ],
		] );
	}

	public function testSubject_whenAliasesDeserializerThrows_throws() {
		$this->expectException( ChangeOpDeserializationException::class );

		$this->aliasesChangeOpDeserializerMock->method( 'createEntityChangeOp' )
			->willThrowException( $this->createMock( ChangeOpDeserializationException::class ) );

		$this->createEntityChangeOpWithTestSubject( [
			'aliases' => [ 'en' => [ 'language' => 'en', 'value' => 'foo' ] ],
		] );
	}

	private function createEntityChangeOpWithTestSubject( $changeRequest ) {
		$serializer = new FingerprintChangeOpDeserializer(
			$this->labelsChangeOpDeserializerMock,
			$this->descriptionsChangeOpDeserializerMock,
			$this->aliasesChangeOpDeserializerMock,
			$this->getFingerprintChangeOpFactory()
		);

		return $serializer->createEntityChangeOp( $changeRequest );
	}

	private function getFingerprintChangeOpFactory() {
		$mockProvider = new ChangeOpTestMockProvider( $this );
		return new FingerprintChangeOpFactory( $mockProvider->getMockTermValidatorFactory() );
	}

}
