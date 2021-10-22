<?php

namespace Wikibase\Lib\Tests\Changes;

use Exception;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Changes\EntityChange;
use Wikibase\Lib\Changes\EntityDiffChangedAspects;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Wikibase\Lib\Changes\EntityChange
 * @covers \Wikibase\Lib\Changes\DiffChange
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseChange
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
class EntityChangeTest extends ChangeRowTest {

	/**
	 * @return string
	 */
	protected function getRowClass() {
		return EntityChange::class;
	}

	protected function newEntityChange( EntityId $entityId ) {
		$changeFactory = TestChanges::getEntityChangeFactory();
		$entityChange = $changeFactory->newForEntity( EntityChange::UPDATE, $entityId );

		return $entityChange;
	}

	public function changeProvider() {
		$rowClass = $this->getRowClass();

		$changes = array_filter(
			TestChanges::getChanges(),
			function( EntityChange $change ) use ( $rowClass ) {
				return is_a( $change, $rowClass );
			}
		);

		$cases = array_map(
			function( EntityChange $change ) {
				return [ $change ];
			},
			$changes );

		return $cases;
	}

	/**
	 * @dataProvider changeProvider
	 */
	public function testGetType( EntityChange $entityChange ) {
		$this->assertIsString( $entityChange->getType() );
	}

	public function testMetadata() {
		$entityChange = $this->newEntityChange( new ItemId( 'Q13' ) );

		$entityChange->setMetadata( [
			'kittens' => 3,
			'rev_id' => 23,
			'user_text' => '171.80.182.208',
		] );
		$this->assertHasMetaData(
			[
				'rev_id' => 23,
				'user_text' => '171.80.182.208',
				'comment' => $entityChange->getComment(), // the comment field is magically initialized
			],
			$entityChange
		);

		// override some fields, keep others
		$entityChange->setMetadata( [
			'rev_id' => 25,
			'comment' => 'foo',
		] );
		$this->assertHasMetaData(
			[
				'rev_id' => 25,
				'user_text' => '171.80.182.208',
				'comment' => 'foo', // the comment field is not magically initialized
			],
			$entityChange
		);
	}

	public function testGivenNonEmptyMetadata_getMetaDataInitializesRequiredFields() {
		$entityChange = $this->newEntityChange( new ItemId( 'Q13' ) );

		$entityChange->setMetadata( [
			'user_text' => 'kitten',
		] );

		$this->assertHasMetaData( [
			'user_text' => 'kitten',
			'page_id' => 0,
			'rev_id' => 0,
			'parent_id' => 0,
		], $entityChange );
	}

	public function testGetEmptyMetadata() {
		$entityChange = $this->newEntityChange( new ItemId( 'Q13' ) );

		$entityChange->setMetadata( [
			'kittens' => 3,
			'rev_id' => 23,
			'user_text' => '171.80.182.208',
		] );

		$entityChange->setField( 'info', [] );
		$this->assertEquals(
			[],
			$entityChange->getMetadata()
		);
	}

	public function testGetComment() {
		$entityChange = $this->newEntityChange( new ItemId( 'Q13' ) );

		$this->assertEquals( 'wikibase-comment-update', $entityChange->getComment(), 'comment' );

		$entityChange->setMetadata( [
			'comment' => 'Foo!',
		] );

		$this->assertEquals( 'Foo!', $entityChange->getComment(), 'comment' );
	}

	/**
	 * @dataProvider provideTestAddUserMetadata
	 */
	public function testAddUserMetadata( $repoUserId, $repoUserText, $centralUserId ) {
		$entityChange = $this->getMockBuilder( EntityChange::class )
			->onlyMethods( [
				'setFields',
				'setMetadata',
			] )
			->getMock();

		$entityChange->expects( $this->once() )
			->method( 'setFields' )
			->with( [
				'user_id' => $repoUserId,
			] );

		$entityChange->expects( $this->once() )
			->method( 'setMetadata' )
			->with( [
				'user_text' => $repoUserText,
				'central_user_id' => $centralUserId,
			] );

		$entityChange = TestingAccessWrapper::newFromObject( $entityChange );
		$entityChange->addUserMetadata( $repoUserId, $repoUserText, $centralUserId );
	}

	// See MockRepoClientCentralIdLookup

	public function provideTestAddUserMetadata() {
		return [
			[
				3,
				'Foo',
				-3,
			],

			[
				0,
				'10.11.12.13',
				0,
			],
		];
	}

	public function testSerializes() {
		$info = [ 'field' => 'value' ];
		$expected = '{"field":"value"}';
		$change = new EntityChange( [ 'info' => $info ] );
		$this->assertSame( $expected, $change->getSerializedInfo() );
	}

	public function testSerializeSkips() {
		$info = [ 'field' => 'value', 'evil' => 'nope!' ];
		$expected = '{"field":"value"}';
		$change = new EntityChange( [ 'info' => $info ] );
		$this->assertSame( $expected, $change->getSerializedInfo( [ 'evil' ] ) );
	}

	public function testDoesNotSerializeObjects() {
		// EntityChange only tests for objects if MW_PHPUNIT_TEST is defined,
		// so define it even when not running under MediaWiki PHPUnit
		if ( !defined( 'MW_PHPUNIT_TEST' ) ) {
			define( 'MW_PHPUNIT_TEST', 'not MediaWiki (' . __METHOD__ . ')' );
		}
		$info = [ 'array' => [ 'object' => (object)[] ] ];
		$change = new EntityChange( [ 'info' => $info ] );
		$this->expectException( Exception::class );
		$change->getSerializedInfo();
	}

	public function testSerializeAndUnserializeInfoCompactDiff() {
		$aspects = new EntityDiffChangedAspects(
			[ 'fa' ],
			[],
			[],
			[],
			false
		);
		$info = [ 'compactDiff' => $aspects->serialize() ];
		$change = new EntityChange( [ 'info' => $info ] );
		$change->setField( 'info', $change->getSerializedInfo() );
		$this->assertEquals( [ 'compactDiff' => $aspects ], $change->getInfo() );
	}

	public function testSerializeAndUnserializeInfoCompactDiffBadSerialization() {
		$aspects = new EntityDiffChangedAspects(
			[ 'de' ],
			[],
			[],
			[],
			false
		);
		$info = [ 'compactDiff' => $aspects->toArray() ];
		$change = new EntityChange( [ 'info' => $info ] );
		$change->setField( 'info', $change->getSerializedInfo() );
		$this->assertEquals( $info, $change->getInfo() );
	}

	private function assertHasMetaData( array $data, EntityChange $change ) {
		$meta = $change->getMetadata();

		foreach ( $data as $key => $value ) {
			$this->assertSame( $value, $meta[$key] );
		}
	}

}
