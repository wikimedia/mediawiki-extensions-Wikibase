<?php

namespace Wikibase\Lib\Tests\Changes;

use MediaWikiTestCase;
use MWException;
use Wikibase\ChangeRow;

/**
 * @covers Wikibase\ChangeRow
 *
 * @since 0.2
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseChange
 *
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class ChangeRowTest extends MediaWikiTestCase {

	public function testAgeCalculation() {
		$change = new ChangeRow( array( 'time' => date( 'YmdHis' ) ) );
		$age = $change->getAge();
		$this->assertInternalType( 'int', $age );
		$this->assertGreaterThanOrEqual( 0, $age );
	}

	public function testCanNotCalculateAgeWithoutTime() {
		$change = new ChangeRow();
		$this->setExpectedException( MWException::class );
		$change->getAge();
	}

	public function testReturnsTime() {
		$change = new ChangeRow( array( 'time' => '20130101000000' ) );
		$this->assertSame( '20130101000000', $change->getTime() );
	}

	public function testCanNotReturnTimeWithoutTime() {
		$change = new ChangeRow();
		$this->setExpectedException( MWException::class );
		$change->getTime();
	}

	public function testGetTypeReturnsChange() {
		$change = new ChangeRow();
		$this->assertSame( 'change', $change->getType() );
	}

	public function testReturnsObjectId() {
		$change = new ChangeRow( array( 'object_id' => 'Q1' ) );
		$this->assertSame( 'Q1', $change->getObjectId() );
	}

	public function testCanNotReturnDefaultObjectId() {
		$change = new ChangeRow();
		$this->setExpectedException( MWException::class );
		$change->getObjectId();
	}

	public function testReturnsExistingField() {
		$change = new ChangeRow( array( 'field' => 'value' ) );
		$this->assertSame( 'value', $change->getField( 'field' ) );
	}

	public function testReturnsDefaultForUnknownField() {
		$change = new ChangeRow();
		$this->assertSame( 'default', $change->getField( 'field', 'default' ) );
	}

	public function testCanNotReturnFieldWithoutDefault() {
		$change = new ChangeRow();
		$this->setExpectedException( MWException::class );
		$change->getField( 'field' );
	}

	public function testGetFieldUnserializesInfo() {
		$json = '{"field":"value"}';
		$expected = array( 'field' => 'value' );
		$change = new ChangeRow( array( 'info' => $json ) );
		$this->assertSame( $expected, $change->getField( 'info' ) );
	}

	public function testReturnsFields() {
		$change = new ChangeRow( array( 'field' => 'value' ) );
		$this->assertSame( array( 'id' => null, 'field' => 'value' ), $change->getFields() );
	}

	public function testGetFieldsUnserializesInfo() {
		$json = '{"field":"value"}';
		$expected = array( 'field' => 'value' );
		$change = new ChangeRow( array( 'info' => $json ) );
		$this->assertSame( array( 'id' => null, 'info' => $expected ), $change->getFields() );
	}

	public function testSerializes() {
		$info = array( 'field' => 'value' );
		$expected = '{"field":"value"}';
		$change = new ChangeRow();
		$this->assertSame( $expected, $change->serializeInfo( $info ) );
	}

	public function testDoesNotSerializeObjects() {
		$info = array( 'array' => array( 'object' => new ChangeRow() ) );
		$change = new ChangeRow();
		$this->setExpectedException( MWException::class );
		$change->serializeInfo( $info );
	}

	public function testUnserializesJson() {
		$json = '{"field":"value"}';
		$expected = array( 'field' => 'value' );
		$change = new ChangeRow();
		$this->assertSame( $expected, $change->unserializeInfo( $json ) );
	}

	public function testUnserializesPhpSerializations() {
		$serialization = 'a:1:{s:5:"field";s:5:"value";}';
		$expected = array( 'field' => 'value' );
		$change = new ChangeRow();
		$this->assertSame( $expected, $change->unserializeInfo( $serialization ) );
	}

	public function testCanNotUnserializeWithoutObjectId() {
		$change = new ChangeRow();
		$this->setExpectedException( MWException::class );
		$change->unserializeInfo( 's:5:"value";' );
	}

	public function testCanNotUnserializeNonArrays() {
		$change = new ChangeRow( array( 'object_id' => 'Q1' ) );

		\MediaWiki\suppressWarnings();
		$info = $change->unserializeInfo( 's:5:"value";' );
		\MediaWiki\restoreWarnings();

		$this->assertSame( array(), $info );
	}

	public function testSetsField() {
		$change = new ChangeRow();
		$change->setField( 'field', 'value' );
		$this->assertSame( 'value', $change->getField( 'field' ) );
	}

	public function testSetsFields() {
		$change = new ChangeRow();
		$change->setFields( array( 'field' => 'value' ) );
		$this->assertSame( 'value', $change->getField( 'field' ) );
	}

	public function testOverridesFieldsByDefault() {
		$change = new ChangeRow( array( 'field' => 'old' ) );
		$change->setFields( array( 'field' => 'new' ) );
		$this->assertSame( 'new', $change->getField( 'field' ) );
	}

	public function testDoesNotOverrideFields() {
		$change = new ChangeRow( array( 'field' => 'old' ) );
		$change->setFields( array( 'field' => 'new' ), false );
		$this->assertSame( 'old', $change->getField( 'field' ) );
	}

	public function testReturnsId() {
		$change = new ChangeRow( array( 'id' => 1 ) );
		$this->assertSame( 1, $change->getId() );
	}

	public function testDefaultIdIsNull() {
		$change = new ChangeRow();
		$this->assertNull( $change->getId() );
	}

	public function testHasKnownField() {
		$change = new ChangeRow( array( 'key' => 'value' ) );
		$this->assertTrue( $change->hasField( 'key' ) );
	}

	public function testDoesNotHaveUnknownField() {
		$change = new ChangeRow();
		$this->assertFalse( $change->hasField( 'unknown' ) );
	}

	public function testAlwaysHasIdField() {
		$change = new ChangeRow();
		$this->assertTrue( $change->hasField( 'id' ) );
	}

}
