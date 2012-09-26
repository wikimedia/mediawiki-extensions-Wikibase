<?php

namespace Wikibase\Test;
use Wikibase\PropertyContent, Wikibase\EntityContent;

/**
 * Tests for the Wikibase\PropertyContent class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
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

		$dataTypes = \Wikibase\Settings::get( 'dataTypes' );
		$content->getProperty()->setDataTypeById( array_shift( $dataTypes ) );

		return $content;
	}

	public function testLabelUniquenessRestriction() {
		\Wikibase\StoreFactory::getStore()->newTermCache()->clear();

		$propertyContent = PropertyContent::newEmpty();
		$propertyContent->getProperty()->setLabel( 'en', 'testLabelUniquenessRestriction' );
		$propertyContent->getProperty()->setLabel( 'de', 'testLabelUniquenessRestriction' );

		$status = $propertyContent->save( 'create property', null, EDIT_NEW );
		$this->assertTrue( $status->isOK(), "property creation should work" );

		$propertyContent1 = PropertyContent::newEmpty();
		$propertyContent1->getProperty()->setLabel( 'nl', 'testLabelUniquenessRestriction' );

		$status = $propertyContent1->save( 'create property', null, EDIT_NEW );
		$this->assertTrue( $status->isOK(), "property creation should work" );

		$propertyContent1->getProperty()->setLabel( 'en', 'testLabelUniquenessRestriction' );

		$status = $propertyContent1->save( 'create property' );
		$this->assertFalse( $status->isOK(), "saving a property with duplicate label+lang should not work" );
		$this->assertTrue( $status->hasMessage( 'wikibase-error-label-not-unique-wikibase-property' ) );
	}

}
