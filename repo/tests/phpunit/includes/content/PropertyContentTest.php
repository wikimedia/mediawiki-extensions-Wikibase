<?php

namespace Wikibase\Test;

use Wikibase\PropertyContent;
use Wikibase\EntityContent;
use Wikibase\StoreFactory;

/**
 * @covers Wikibase\PropertyContent
 *
 * @since 0.1
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseProperty
 * @group WikibaseRepo
 * @group WikibaseContent
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyContentTest extends EntityContentTest {

	/**
	 * @see EntityContentTest::getContentClass
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	protected function getContentClass() {
		return '\Wikibase\PropertyContent';
	}

	/**
	 * @see EntityContentTest::newEmpty
	 *
	 * @since 0.2
	 *
	 * @return EntityContent
	 */
	protected function newEmpty() {
		$content = PropertyContent::newEmpty();
		$content->getProperty()->setDataTypeId( 'string' );

		return $content;
	}

	public function testLabelUniquenessRestriction() {
		StoreFactory::getStore()->getTermIndex()->clear();

		$propertyContent = PropertyContent::newEmpty();
		$propertyContent->getProperty()->setLabel( 'en', 'testLabelUniquenessRestriction' );
		$propertyContent->getProperty()->setLabel( 'de', 'testLabelUniquenessRestriction' );
		$propertyContent->getProperty()->setDataTypeId( 'wikibase-item' );

		$status = $propertyContent->save( 'create property', null, EDIT_NEW );
		$this->assertTrue( $status->isOK(), "property creation should work" );

		$propertyContent1 = PropertyContent::newEmpty();
		$propertyContent1->getProperty()->setLabel( 'nl', 'testLabelUniquenessRestriction' );
		$propertyContent1->getProperty()->setDataTypeId( 'wikibase-item' );

		$status = $propertyContent1->save( 'create property', null, EDIT_NEW );
		$this->assertTrue( $status->isOK(), "property creation should work" );

		$propertyContent1->getProperty()->setLabel( 'en', 'testLabelUniquenessRestriction' );

		$status = $propertyContent1->save( 'save property' );
		$this->assertFalse( $status->isOK(), "saving a property with duplicate label+lang should not work" );
		$this->assertTrue( $status->hasMessage( 'wikibase-error-label-not-unique-wikibase-property' ) );
	}

	public function testLabelEntityIdRestriction() {
		StoreFactory::getStore()->getTermIndex()->clear();

		$propertyContent = PropertyContent::newEmpty();
		$propertyContent->getProperty()->setLabel( 'en', 'testLabelEntityIdRestriction' );
		$propertyContent->getProperty()->setDataTypeId( 'wikibase-item' );

		$status = $propertyContent->save( 'create property', null, EDIT_NEW );
		$this->assertTrue( $status->isOK(), "property creation should work" );

		// save a property
		$propertyContent->getProperty()->setLabel( 'de', 'testLabelEntityIdRestriction' );

		$status = $propertyContent->save( 'save property' );
		$this->assertTrue( $status->isOK(), "saving a property should work" );

		// save a property with a valid item id as label
		$propertyContent->getProperty()->setLabel( 'fr', 'Q42' );

		$status = $propertyContent->save( 'save property' );
		$this->assertTrue( $status->isOK(), "saving a property with a valid item id as label should work" );

		// save a property with a valid property id as label
		$propertyContent->getProperty()->setLabel( 'nl', 'P23' );

		$status = $propertyContent->save( 'save property' );
		$this->assertFalse( $status->isOK(), "saving a proeprty with a valid property id as label should not work" );
		$this->assertTRue( $status->hasMessage( 'wikibase-error-label-no-entityid' ) );
	}

}
