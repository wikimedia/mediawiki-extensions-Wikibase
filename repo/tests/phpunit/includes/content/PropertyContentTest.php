<?php

namespace Wikibase\Test;

use Wikibase\PropertyContent;
use Wikibase\StoreFactory;

/**
 * @covers Wikibase\PropertyContent
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
	 */
	protected function getContentClass() {
		return '\Wikibase\PropertyContent';
	}

	/**
	 * @see EntityContentTest::newEmpty
	 */
	protected function newEmpty() {
		$content = PropertyContent::newEmpty();
		$content->getProperty()->setDataTypeId( 'string' );

		return $content;
	}

	public function testLabelUniquenessRestriction() {
		StoreFactory::getStore()->getTermIndex()->clear();
		$prefix = get_class( $this ) . '/';

		$propertyContent = PropertyContent::newEmpty();
		$propertyContent->getProperty()->setLabel( 'en', $prefix . 'testLabelUniquenessRestriction' );
		$propertyContent->getProperty()->setLabel( 'de', $prefix . 'testLabelUniquenessRestriction' );
		$propertyContent->getProperty()->setDataTypeId( 'wikibase-item' );

		$status = $propertyContent->save( 'create property', null, EDIT_NEW );
		$this->assertTrue( $status->isOK(), "property creation should work" );

		$propertyContent1 = PropertyContent::newEmpty();
		$propertyContent1->getProperty()->setLabel( 'nl', $prefix . 'testLabelUniquenessRestriction' );
		$propertyContent1->getProperty()->setDataTypeId( 'wikibase-item' );

		$status = $propertyContent1->save( 'create property', null, EDIT_NEW );
		$this->assertTrue( $status->isOK(), "property creation should work" );

		$propertyContent1->getProperty()->setLabel( 'en', $prefix . 'testLabelUniquenessRestriction' );

		$status = $propertyContent1->save( 'save property' );
		$this->assertFalse( $status->isOK(), "saving a property with duplicate label+lang should not work" );
		$this->assertTrue( $status->hasMessage( 'wikibase-error-label-not-unique-wikibase-property' ) );
	}

	public function testLabelEntityIdRestriction() {
		StoreFactory::getStore()->getTermIndex()->clear();
		$prefix = get_class( $this ) . '/';

		$propertyContent = PropertyContent::newEmpty();
		$propertyContent->getProperty()->setLabel( 'en', $prefix . 'testLabelEntityIdRestriction' );
		$propertyContent->getProperty()->setDataTypeId( 'wikibase-item' );

		$status = $propertyContent->save( 'create property', null, EDIT_NEW );
		$this->assertTrue( $status->isOK(), "property creation should work" );

		// save a property
		$propertyContent->getProperty()->setLabel( 'de', $prefix . 'testLabelEntityIdRestriction' );

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
		$this->assertTrue( $status->hasMessage( 'wikibase-error-label-no-entityid' ) );
	}

}
