<?php

namespace Wikibase\Repo\Tests\Specials\HTMLForm;

use Wikibase\Repo\Specials\HTMLForm\HTMLContentLanguageField;

/**
 * @covers Wikibase\Repo\Specials\HTMLForm\HTMLContentLanguageField
 *
 * @license GPL-2.0+
 * @group Wikibase
 */
class HTMLContentLanguageFieldTest extends \MediaWikiTestCase {

	/**
	 * @dataProvider provideVariantsToDefineOptionsForTheField
	 */
	public function testDoesNotAllowToSetOptions_WhenCreated( $params ) {

		$this->setExpectedException( \InvalidArgumentException::class );
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
			'parent' => $this->createNewContextSourceWithLanguage( 'some-language' ),
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

	private function createNewContextSourceWithLanguage( $langCode ) {
		$mock = $this->getMock( \IContextSource::class );

		$language = new \Language();
		$language->setCode( $langCode );

		$mock->method( 'getLanguage' )->willReturn( $language );

		return $mock;
	}

	/**
	 * @param array $params
	 *
	 * @return HTMLContentLanguageField
	 */
	private function createField( array $params ) {
		$requiredByBaseClass = [ 'fieldname' => 'some-name', ];

		return new HTMLContentLanguageField( array_merge( $requiredByBaseClass, $params ) );
	}

}
