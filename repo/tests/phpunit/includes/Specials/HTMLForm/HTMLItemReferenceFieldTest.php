<?php

namespace Wikibase\Repo\Tests\Specials\HTMLForm;

use HTMLForm;
use MediaWikiIntegrationTestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\InMemoryEntityLookup;
use Wikibase\Repo\Specials\HTMLForm\HTMLItemReferenceField;

/**
 * @covers \Wikibase\Repo\Specials\HTMLForm\HTMLItemReferenceField
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class HTMLItemReferenceFieldTest extends MediaWikiIntegrationTestCase {
	/**
	 * @var InMemoryEntityLookup
	 */
	private $entityLookup;

	protected function setUp(): void {
		$this->entityLookup = new InMemoryEntityLookup();
	}

	/**
	 * Test ensures that client won't be able to set type of input field, because it will not work
	 * with any type except "text" which it sets internally {@see testSetsTypeToText_WhenCreated}
	 */
	public function testThrowsExceptionIfTypeParameterIsSet_WhenCreated() {
		$this->expectException( \Exception::class );

		$this->createField( [ 'type' => 'some-type' ] );
	}

	public function testSetsTypeToText_WhenCreated() {
		$field = $this->createField();

		$this->assertSame( 'text', $field->mParams['type'] );
	}

	public function testValidationPasses_WhenEmptyStringGivenAndFieldIsNotRequired() {
		$field = $this->createField();

		$result = $field->validate( '', [] );

		$this->assertTrue( $result );
	}

	public function testValidationFailsWithInvalidFormatMessage_WhenEnteredTextDoesNotMatchItemIdFormat() {
		$field = $this->createField();

		/** @var \Message $failureMessage */
		$failureMessage = $field->validate( 'x', [] );

		$this->assertSame( 'wikibase-item-reference-edit-invalid-format', $failureMessage->getKey() );
	}

	public function testValidationFailsWithNonexistentItemMessage_WhenItemHavingEnteredIdDoesNotExist() {
		$field = $this->createField();

		/** @var \Message $failureMessage */
		$failureMessage = $field->validate( 'Q2', [] );

		$this->assertSame( 'wikibase-item-reference-edit-nonexistent-item', $failureMessage->getKey() );
	}

	public function testValidationCallbackExecuted_WhenReferencedItemExists() {
		$this->givenItemExists( $existingItemId = 'Q1' );
		$field = $this->createField(
			[
				'validation-callback' => function () {
					return wfMessage( 'some-message' );
				},
			]
		);

		/** @var \Message $failureMessage */
		$failureMessage = $field->validate( $existingItemId, [] );

		$this->assertSame( 'some-message', $failureMessage->getKey() );
	}

	/**
	 * @return HTMLItemReferenceField
	 */
	protected function createField( $params = [] ) {
		$htmlFormMock = $this->createMock( HTMLForm::class );
		$language = $this->getServiceContainer()->getLanguageFactory()->getLanguage( 'en' );
		$htmlFormMock->method( 'getLanguage' )->willReturn( $language );
		$htmlFormMock->method( 'msg' )->willReturnCallback( 'wfMessage' );

		$paramsRequiredByParentClass = [
			'fieldname' => 'some-name',
			'parent' => $htmlFormMock,
		];

		return new HTMLItemReferenceField( array_merge( $paramsRequiredByParentClass, $params ), $this->entityLookup );
	}

	/**
	 * @param string $itemId
	 */
	private function givenItemExists( $itemId ) {
		$this->entityLookup->addEntity( new Item( new ItemId( $itemId ) ) );
	}

}
