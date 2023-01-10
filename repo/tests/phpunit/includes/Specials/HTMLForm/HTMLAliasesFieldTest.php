<?php

namespace Wikibase\Repo\Tests\Specials\HTMLForm;

use HTMLForm;
use MediaWikiIntegrationTestCase;
use Wikibase\Repo\Specials\HTMLForm\HTMLAliasesField;

/**
 * @covers \Wikibase\Repo\Specials\HTMLForm\HTMLAliasesField
 *
 * @group Wikibase
 * @license GPL-2.0-or-later
 */
class HTMLAliasesFieldTest extends MediaWikiIntegrationTestCase {

	public function testThrowsExceptionIfFilterCallbackParameterIsSet_WhenCreated() {
		$this->expectException( \Exception::class );

		$this->createField(
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
		$this->expectException( \Exception::class );

		$this->createField(
			[
				'fieldname' => 'some-name',
				'type' => 'some-type',
			]
		);
	}

	public function testSetsTypeToText_WhenCreated() {
		$field = $this->createField(
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
		$htmlFormMock = $this->createMock( HTMLForm::class );
		$language = $this->getServiceContainer()->getLanguageFactory()->getLanguage( 'en' );
		$htmlFormMock->method( 'getLanguage' )->willReturn( $language );
		$htmlFormMock->method( 'msg' )->willReturnCallback( 'wfMessage' );

		$requiredByBaseClass = [
			'fieldname' => 'some-name',
			'parent' => $htmlFormMock,
		];

		return new HTMLAliasesField( array_merge( $requiredByBaseClass, $params ) );
	}

}
