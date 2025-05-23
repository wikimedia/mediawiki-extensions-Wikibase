<?php

namespace Wikibase\Repo\Tests\ChangeOp;

use DataValues\NumberValue;
use DataValues\StringValue;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\ChangeOp\ChangeOpException;
use Wikibase\Repo\ChangeOp\ChangeOpQualifier;
use Wikibase\Repo\Store\EntityPermissionChecker;

/**
 * @covers \Wikibase\Repo\ChangeOp\ChangeOpQualifier
 *
 * @group Wikibase
 * @group ChangeOp
 *
 * @license GPL-2.0-or-later
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Daniel Kinzler
 */
class ChangeOpQualifierTest extends \PHPUnit\Framework\TestCase {

	private ChangeOpTestMockProvider $mockProvider;

	protected function setUp(): void {
		parent::setUp();

		$this->mockProvider = new ChangeOpTestMockProvider( $this );
	}

	public static function invalidArgumentProvider(): iterable {
		$item = new Item( new ItemId( 'Q42' ) );

		$guidGenerator = new GuidGenerator();
		$guid = $guidGenerator->newGuid( $item->getId() );
		$validSnak = new PropertyValueSnak( 7201010, new StringValue( 'o_O' ) );
		$validSnakHash = $validSnak->getHash();

		return [
			[ 123, $validSnak, $validSnakHash ],
			[ '', $validSnak, $validSnakHash ],
			[ $guid, $validSnak, 123 ],
		];
	}

	/**
	 * @dataProvider invalidArgumentProvider
	 */
	public function testInvalidConstruct( $statementGuid, $snak, $snakHash ) {
		$this->expectException( InvalidArgumentException::class );
		new ChangeOpQualifier(
			$statementGuid,
			$snak,
			$snakHash,
			$this->mockProvider->getMockSnakValidator()
		);
	}

	public static function changeOpAddProvider(): iterable {
		$snak = new PropertyValueSnak( 2754236, new StringValue( 'test' ) );

		$item = self::newItem( $snak );
		$statements = $item->getStatements()->toArray();
		/** @var Statement $statement */
		$statement = reset( $statements );
		$newQualifier = new PropertyValueSnak( 78462378, new StringValue( 'newQualifier' ) );
		$changeOpParams = [
			$statement->getGuid(),
			$newQualifier,
			'',
		];
		$snakHash = $newQualifier->getHash();

		return [
			[ $item, $changeOpParams, $snakHash ],
		];
	}

	public static function changeOpSetProvider(): iterable {
		$snak = new PropertyValueSnak( 2754236, new StringValue( 'test' ) );

		$item = self::newItem( $snak );
		$statements = $item->getStatements()->toArray();
		/** @var Statement $statement */
		$statement = reset( $statements );
		$newQualifier = new PropertyValueSnak( 78462378, new StringValue( 'newQualifier' ) );
		$statement->getQualifiers()->addSnak( $newQualifier );
		$snakHash = $newQualifier->getHash();
		$changedQualifier = new PropertyValueSnak( 78462378, new StringValue( 'changedQualifier' ) );
		$changeOpParams = [
			$statement->getGuid(),
			$changedQualifier,
			$snakHash,
		];

		return [
			[ $item, $changeOpParams, $changedQualifier->getHash() ],
		];
	}

	/**
	 * @dataProvider changeOpAddProvider
	 * @dataProvider changeOpSetProvider
	 */
	public function testApplySetQualifier( Item $item, $changeOpParams, $snakHash ) {
		$changeOpParams[] = $this->mockProvider->getMockSnakValidator();
		$changeOp = new ChangeOpQualifier( ...$changeOpParams );

		$changeOpResult = $changeOp->apply( $item );
		$statements = $item->getStatements()->toArray();
		/** @var Statement $statement */
		$statement = reset( $statements );
		$qualifiers = $statement->getQualifiers();
		$this->assertTrue( $qualifiers->hasSnakHash( $snakHash ), 'Qualifier not found' );
		$this->assertTrue( $changeOpResult->isEntityChanged() );
	}

	/**
	 * @param Snak $snak
	 *
	 * @return Item
	 */
	private static function newItem( Snak $snak ) {
		$item = new Item( new ItemId( 'Q123' ) );

		$item->getStatements()->addNewStatement(
			$snak,
			null,
			null,
			$item->getId()->getSerialization() . '$D8404CDA-25E4-4334-AG03-A3290BCD9CQP'
		);

		return $item;
	}

	public static function applyInvalidProvider() {
		$p11 = new NumericPropertyId( 'P11' );
		$q17 = new ItemId( 'Q17' );

		$item = new Item( $q17 );
		$goodGuid = 'GUID';
		$badGuid = 'UNKNOWN-GUID';

		$oldSnak = new PropertyValueSnak( $p11, new StringValue( "old qualifier" ) );

		$snak = new PropertyNoValueSnak( $p11 );
		$qualifiers = new SnakList( [ $oldSnak ] );
		$item->getStatements()->addNewStatement( $snak, $qualifiers, null, $goodGuid );

		$goodSnak = new PropertyValueSnak( $p11, new StringValue( 'good' ) );

		$badSnakHash = sha1( "dummy" );

		return [
			'unknown statement guid' => [ $item, $badGuid, $goodSnak, '' ],
			'unknown snak hash' => [ $item, $goodGuid, $goodSnak, $badSnakHash ],
		];
	}

	/**
	 * @dataProvider applyInvalidProvider
	 */
	public function testApplyInvalid(
		EntityDocument $entity,
		$statementGuid,
		Snak $snak,
		$snakHash
	) {
		$changeOpQualifier = new ChangeOpQualifier(
			$statementGuid,
			$snak,
			$snakHash,
			$this->mockProvider->getMockSnakValidator()
		);

		$this->expectException( ChangeOpException::class );
		$changeOpQualifier->apply( $entity );
	}

	public static function validateProvider() {
		$p11 = new NumericPropertyId( 'P11' );
		$q17 = new ItemId( 'Q17' );

		$item = new Item( $q17 );
		$guid = 'GUID';

		$oldSnak = new PropertyValueSnak( $p11, new StringValue( "old qualifier" ) );

		$snak = new PropertyNoValueSnak( $p11 );
		$qualifiers = new SnakList( [ $oldSnak ] );
		$item->getStatements()->addNewStatement( $snak, $qualifiers, null, $guid );

		//NOTE: the mock validator will consider the string "INVALID" to be invalid.
		$badSnak = new PropertyValueSnak( $p11, new StringValue( 'INVALID' ) );
		$brokenSnak = new PropertyValueSnak( $p11, new NumberValue( 23 ) );

		$snakHash = $oldSnak->getHash();

		return [
			'invalid snak value' => [ $item, $guid, $badSnak, '' ],
			'invalid snak value type' => [ $item, $guid, $brokenSnak, $snakHash ],
		];
	}

	/**
	 * @dataProvider validateProvider
	 */
	public function testValidate( EntityDocument $entity, $statementGuid, Snak $snak, $snakHash ) {
		$changeOpQualifier = new ChangeOpQualifier(
			$statementGuid,
			$snak,
			$snakHash,
			$this->mockProvider->getMockSnakValidator()
		);

		$result = $changeOpQualifier->validate( $entity );
		$this->assertFalse( $result->isValid(), 'isValid()' );
	}

	public function testGetActions() {
		$changeOp = new ChangeOpQualifier(
			'guid',
			new PropertyNoValueSnak( new NumericPropertyId( 'P11' ) ),
			'snakhash',
			$this->mockProvider->getMockSnakValidator()
		);

		$this->assertEquals( [ EntityPermissionChecker::ACTION_EDIT ], $changeOp->getActions() );
	}

}
