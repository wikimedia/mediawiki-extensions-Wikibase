<?php

namespace Wikibase\Test;

use Wikibase\PropertyContent;
use Wikibase\EntityContent;

/**
 * @covers Wikibase\PropertyContent
 *
 * @file
 * @since 0.1
 *
 * @ingroup WikibaseRepoTest
 * @ingroup Test
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
		\Wikibase\StoreFactory::getStore()->getTermIndex()->clear();

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

}
