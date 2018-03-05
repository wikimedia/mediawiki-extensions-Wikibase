<?php

namespace Wikibase\Repo\Tests\Specials\HTMLForm;

use Wikibase\Repo\Specials\HTMLForm\HTMLAliasesField;

/**
 * @covers Wikibase\Repo\Specials\HTMLForm\HTMLAliasesField
 *
 * @group Wikibase
 * @license GPL-2.0-or-later
 */
class HTMLAliasesFieldTest extends \MediaWikiTestCase {

	public function testThrowsExceptionIfFilterCallbackParameterIsSet_WhenCreated() {
		$this->setExpectedException( \Exception::class );

		new HTMLAliasesField(
			[
				'fieldname' => 'some-name',
				'filter-callback' => function () {
				},
			]
		);
	}

	/**
	 * Test ensures that client won't be able to set type of input field, because it will not work
	 * with any type except "text" which it sets internally {@see testSetsTypeToText_WhenCreated}
	 */
	public function testThrowsExceptionIfTypeParameterIsSet_WhenCreated() {
		$this->setExpectedException( \Exception::class );

		new HTMLAliasesField(
			[
				'fieldname' => 'some-name',
				'type' => 'some-type',
			]
		);
	}

	public function testSetsTypeToText_WhenCreated() {
		$field = new HTMLAliasesField(
			[
				'fieldname' => 'some-name',
			]
		);

		$this->assertSame( 'text', $field->mParams['type'] );
	}

	public function testConvertsToArrayAndRemovesExtraSpaces_WhenFilters() {
		$field = $this->createField();

		$result = $field->filter( ' a | b ', [] );

		$this->assertSame( [ 'a', 'b' ], $result );
	}

	public function testRemovesEmptyValues_WhenFilters() {
		$field = $this->createField();

		$result = $field->filter( 'a| |b', [] );

		$this->assertSame( [ 'a', 'b' ], $result );
	}

	public function testValidationFailsWithGenericMessage_WhenRequiredAndEmptyArrayGivenAsValue() {
		$field = $this->createField( [ 'required' => true ] );

		/** @var \Message $failureMessage */
		$failureMessage = $field->validate( [], [] );

		$this->assertSame( 'htmlform-required', $failureMessage->getKey() );
	}

	public function testValidationPasses_WhenRequiredAndNonEmptyArrayGivenAsValue() {
		$field = $this->createField( [ 'required' => true ] );

		$result = $field->validate( [ 'a' ], [] );

		$this->assertTrue( $result );
	}

	public function testValidationPasses_WhenNotRequiredAndEmptyArrayGivenAsValue() {
		$field = $this->createField( [ 'required' => false ] );

		$result = $field->validate( [], [] );

		$this->assertTrue( $result );
	}

	/**
	 * @return HTMLAliasesField
	 */
	public function createField( array $params = [] ) {
		$requiredByBaseClass = [ 'fieldname' => 'some-name', ];

		return new HTMLAliasesField( array_merge( $requiredByBaseClass, $params ) );
	}

}
