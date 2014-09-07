<?php

namespace Wikibase\Test;

use DataTypes\DataType;
use InvalidArgumentException;
use Language;
use Message;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\EntityRevision;
use Wikibase\PropertyView;

/**
 * @covers Wikibase\PropertyView
 *
 * @group Wikibase
 * @group WikibasePropertyView
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class PropertyViewTest extends \PHPUnit_Framework_TestCase {

	public function provideEntityRevisions() {
		$revisions = array();
		$revId = 1234;
		$timestamp = wfTimestamp( TS_MW );

		$property = Property::newEmpty();
		$property->setDataTypeId( 'string' );
		$revisions['new property'] = array( new EntityRevision( $property, $revId++, $timestamp ) );

		$property = $property->copy();
		$property->setId( 123 );
		$revisions['existing property'] = array( new EntityRevision( $property, $revId++, $timestamp ) );

		return $revisions;
	}

	/**
	 * @dataProvider provideEntityRevisions
	 *
	 * @param EntityRevision $entityRevision
	 */
	public function testGetHtml( EntityRevision $entityRevision ) {
		$propertyView = $this->getPropertyView();
		$html = $propertyView->getHtml( $entityRevision );
		$id = $entityRevision->getEntity()->getId();

		if ( $id === null ) {
			$this->assertContains( 'new', $html, "Html for new properties should cotain keyword 'new'" );
		} else {
			$this->assertContains( $id->getSerialization(), $html, "Html for existing properties should contain the serialized id" );
		}

		$this->assertContains( Property::ENTITY_TYPE, $html, "Html should contain property type" );

		$dataTypeId = $entityRevision->getEntity()->getDataTypeId();
		$dataTypeIdMessage = wfMessage( 'datatypes-type-' . $dataTypeId )->text();
		$this->assertContains( $dataTypeIdMessage, $html, "Html should contain data type id message, which is '$dataTypeIdMessage'" );

		$this->assertContains( '<fingerprint>', $html, "Generated html should contain '<fingerprint>'" );

		if ( defined( 'WB_EXPERIMENTAL_FEATURES' ) && WB_EXPERIMENTAL_FEATURES ) {
			$this->assertContains( '<claims>', $html, "Generated html should contain '<claims>'" );
		} else {
			$this->assertNotContains( '<claims>', $html, "Generated html should not contain '<claims>'" );
		}

		$placeholders = $propertyView->getPlaceholders();
		$placeholderNames = array();
		foreach ( $placeholders as $placeholder => $args ) {
			$placeholderNames[] = $args[0];
			$this->assertContains( $placeholder, $html, "Generated html should contain placeholder for key '{$args[0]}'" );
		}

		if ( $id !== null ) {
			$this->assertContains( 'termbox', $placeholderNames, "Placeholder 'termbox' should be used" );
		}
	}

	/**
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage $entityRevision must contain a Property.
	 */
	public function testGetHtmlFailsForWrongEntityType() {
		$propertyView = $this->getPropertyView();
		$revision = new EntityRevision( Item::newEmpty(), 1234, wfTimestamp( TS_MW ) );

		$propertyView->getHtml( $revision );
	}

	private function getPropertyView() {
		return new PropertyView(
			$this->getViewMock( 'Wikibase\Repo\View\FingerprintView', '<fingerprint>' ),
			$this->getViewMock( 'Wikibase\Repo\View\ClaimsView', '<claims>' ),
			$this->getDataTypeFactoryMock(),
			Language::factory( 'en' )
		);
	}

	private function getViewMock( $className, $returnValue ) {
		$view = $this->getMockBuilder( $className )
			->disableOriginalConstructor()
			->getMock();

		$view->expects( $this->any() )
			->method( 'getHtml' )
			->will( $this->returnValue( $returnValue ) );

		return $view;
	}

	private function getDataTypeFactoryMock() {
		$dataTypeFactory = $this->getMockBuilder( 'DataTypes\DataTypeFactory' )
			->disableOriginalConstructor()
			->getMock();

		$dataTypeFactory->expects( $this->any() )
			->method( 'getType' )
			->will( $this->returnCallback( function( $dataTypeId ) {
				return new DataType( $dataTypeId, $dataTypeId, array() );
			} ) );

		return $dataTypeFactory;
	}

}
