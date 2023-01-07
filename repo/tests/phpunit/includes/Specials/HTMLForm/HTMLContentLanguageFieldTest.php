<?php

namespace Wikibase\Repo\Tests\Specials\HTMLForm;

use HTMLForm;
use MediaWikiIntegrationTestCase;
use Wikibase\Repo\Specials\HTMLForm\HTMLContentLanguageField;

/**
 * @covers \Wikibase\Repo\Specials\HTMLForm\HTMLContentLanguageField
 *
 * @license GPL-2.0-or-later
 * @group Wikibase
 */
class HTMLContentLanguageFieldTest extends MediaWikiIntegrationTestCase {

	/**
	 * @dataProvider provideVariantsToDefineOptionsForTheField
	 */
	public function testDoesNotAllowToSetOptions_WhenCreated( $params ) {

		$this->expectException( \InvalidArgumentException::class );
		$this->createField( $params );
	}

	public function provideVariantsToDefineOptionsForTheField() {
		return [
			'options' => [
				[
					'options' => [],
				],
			],
			'options-messages' => [
				[
					'options-messages' => [],
				],
			],
			'options-message' => [
				[
					'options-message' => [],
				],
			],

		];
	}

	public function testSetsDefaultValueToLanguageFromParentElement_WhenCreatedAndDefaultIsNotDefined() {
		$params = [
			'parent' => $this->createNewHTMLFormWithLanguage( 'some-language' ),
		];

		$field = $this->createField( $params );

		$this->assertSame( 'some-language', $field->getDefault() );
	}

	public function testUsesDefaultValue_WhenDefaultIsDefined() {
		$params = [
			'default' => 'default-language',
		];

		$field = $this->createField( $params );

		$this->assertSame( 'default-language', $field->getDefault() );
	}

	private function createNewHTMLFormWithLanguage( $langCode ) {
		$mock = $this->createMock( HTMLForm::class );

		$language = $this->getServiceContainer()->getLanguageFactory()->getLanguage( $langCode );

		$mock->method( 'getLanguage' )->willReturn( $language );

		return $mock;
	}

	/**
	 * @param array $params
	 *
	 * @return HTMLContentLanguageField
	 */
	private function createField( array $params ) {
		$requiredByBaseClass = [
			'fieldname' => 'some-name',
			'parent' => $this->createNewHTMLFormWithLanguage( 'en' ),
		];

		return new HTMLContentLanguageField( array_merge( $requiredByBaseClass, $params ) );
	}

}
