<?php

namespace Wikibase\Lib\Tests\Changes;

use MediaWikiTestCase;
use MWException;
use Wikibase\ChangeRow;
use Wikibase\EntityChange;

/**
 * @covers Wikibase\ChangeRow
 * @covers Wikibase\EntityChange
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseChange
 *
 * @license GPL-2.0+
 * @author Thiemo Kreuz
 */
class ChangeRowTest extends MediaWikiTestCase {

	public function testAgeCalculation() {
		$change = $this->newChangeRow( [ 'time' => date( 'YmdHis' ) ] );
		$age = $change->getAge();
		$this->assertInternalType( 'int', $age );
		$this->assertGreaterThanOrEqual( 0, $age );
	}

	public function testCanNotCalculateAgeWithoutTime() {
		$change = $this->newChangeRow();
		$this->setExpectedException( MWException::class );
		$change->getAge();
	}

	public function testReturnsTime() {
		$change = $this->newChangeRow( [ 'time' => '20130101000000' ] );
		$this->assertSame( '20130101000000', $change->getTime() );
	}

	public function testCanNotReturnTimeWithoutTime() {
		$change = $this->newChangeRow();
		$this->setExpectedException( MWException::class );
		$change->getTime();
	}

	public function testReturnsObjectId() {
		$change = $this->newChangeRow( [ 'object_id' => 'Q1' ] );
		$this->assertSame( 'Q1', $change->getObjectId() );
	}

	public function testCanNotReturnDefaultObjectId() {
		$change = $this->newChangeRow();
		$this->setExpectedException( MWException::class );
		$change->getObjectId();
	}

	public function testReturnsExistingField() {
		$change = $this->newChangeRow( [ 'field' => 'value' ] );
		$this->assertSame( 'value', $change->getField( 'field' ) );
	}

	public function testCanNotReturnFieldWithoutDefault() {
		$change = $this->newChangeRow();
		$this->setExpectedException( MWException::class );
		$change->getField( 'field' );
	}

	public function testGetInfoUnserializesInfo() {
		$json = '{"field":"value"}';
		$expected = [ 'field' => 'value' ];
		$change = $this->newChangeRow( [ 'info' => $json ] );
		$this->assertSame( $expected, $change->getInfo() );
	}

	public function testReturnsFields() {
		$change = $this->newChangeRow( [ 'field' => 'value' ] );
		$this->assertSame( [ 'id' => null, 'field' => 'value' ], $change->getFields() );
	}

	public function testGetFieldsUnserializesInfo() {
		$json = '{"field":"value"}';
		$expected = [ 'field' => 'value' ];
		$change = $this->newChangeRow( [ 'info' => $json ] );
		$this->assertSame( [ 'id' => null, 'info' => $expected ], $change->getFields() );
	}

	public function testUnserializesJson() {
		$json = '{"field":"value"}';
		$expected = [ 'field' => 'value' ];
		$change = $this->newChangeRow( [ 'info' => $json ] );
		$this->assertSame( $expected, $change->getInfo() );
	}

	public function testCanNotUnserializeWithoutObjectId() {
		$change = $this->newChangeRow( [ 'info' => 's:5:"value";' ] );
		$this->setExpectedException( MWException::class );
		$change->getInfo();
	}

	public function testCanNotUnserializeNonArrays() {
		$change = $this->newChangeRow( [
			'object_id' => 'Q1',
			'info' => 's:5:"value";',
		] );

		\MediaWiki\suppressWarnings();
		$info = $change->getInfo();
		\MediaWiki\restoreWarnings();

		$this->assertSame( [], $info );
	}

	public function testSetsField() {
		$change = $this->newChangeRow();
		$change->setField( 'field', 'value' );
		$this->assertSame( 'value', $change->getField( 'field' ) );
	}

	public function testSetsFields() {
		$change = $this->newChangeRow();
		$change->setFields( [ 'field' => 'value' ] );
		$this->assertSame( 'value', $change->getField( 'field' ) );
	}

	public function testOverridesFieldsByDefault() {
		$change = $this->newChangeRow( [ 'field' => 'old' ] );
		$change->setFields( [ 'field' => 'new' ] );
		$this->assertSame( 'new', $change->getField( 'field' ) );
	}

	public function testReturnsId() {
		$change = $this->newChangeRow( [ 'id' => 1 ] );
		$this->assertSame( 1, $change->getId() );
	}

	public function testDefaultIdIsNull() {
		$change = $this->newChangeRow();
		$this->assertNull( $change->getId() );
	}

	public function testHasKnownField() {
		$change = $this->newChangeRow( [ 'key' => 'value' ] );
		$this->assertTrue( $change->hasField( 'key' ) );
	}

	public function testDoesNotHaveUnknownField() {
		$change = $this->newChangeRow();
		$this->assertFalse( $change->hasField( 'unknown' ) );
	}

	public function testAlwaysHasIdField() {
		$change = $this->newChangeRow();
		$this->assertTrue( $change->hasField( 'id' ) );
	}

	/**
	 * @param array $fields
	 *
	 * @return ChangeRow
	 */
	private function newChangeRow( array $fields = [] ) {
		return new EntityChange( $fields );
	}

}
