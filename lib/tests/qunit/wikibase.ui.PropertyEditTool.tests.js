/**
 * QUnit tests for property edit tool
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( mw, wb, $, QUnit ) {
	'use strict';

var
	/**
	 * Factory for creating a new PropertyEdiTool suited for testing.
	 *
	 * @param {jQuery} subject
	 * @param {Object} overwrites Allows to give properties which will be overwritten in the
	 *        fabricated PropertyEditTool.
	 * @return {wikibase.ui.PropertyEditTool.EditableValue}
	 */
	newTestPET = function( subject, overwrites ) {
		var propertyEditTool = new wb.ui.PropertyEditTool(); // required for creating suited toolbar

		// Apply options or other overwrites:
		$.extend(
			propertyEditTool,
			overwrites || {}
		);

		propertyEditTool.init( subject, {
			allowsMultipleValues: false // TODO: shouldn't change default here
		} );

		return propertyEditTool;
	},
	/**
	 * Convenience function for testing to create a new EditableValue within the PropertyEditTool.
	 * The given value has to be non-empty, otherwise edit mode will be triggered for the value.
	 *
	 * TODO: there should really be a way in PropertyEditTool to do this. Right now this only works
	 *  because value is non-empty string and we do not use the public enterNewValue() function
	 *  which would mark the new value as pending. Both (empty value or pending) would trigger edit
	 *  mode immediately. The second bad thing is that edit mode can't be closed with saving the new
	 *  value because an API call would be triggered!
	 *
	 * @return {wikibase.ui.PropertyEditTool.EditableValue}
	 */
	addValueToPET = function( propertyEditTool, value ) {
		return propertyEditTool._initSingleValue(
			$( '<div/>' ).append( $( '<div/>', {
				'class': 'wb-value',
				text: value
			} ) )
		);
	};

	QUnit.module( 'wikibase.ui.PropertyEditTool', QUnit.newWbEnvironment() );


	QUnit.test( 'test helper functions for testing PropertyEditTool', function( assert ) {
		var $subject = $( '<div/>' ),
			pet = newTestPET( $subject );

		assert.ok(
			pet instanceof wb.ui.PropertyEditTool,
			'PropertyEditTool test factory returned sufficient instance'
		);

		QUnit.assert.equal(
			pet.getSubject()[0],
			$subject[0],
			'verified subject node of new PropertyEditTool'
		);

		var newVal = addValueToPET( pet, 'foo' );
		assert.ok(
			newVal instanceof wb.ui.PropertyEditTool.EditableValue,
			'addValueToPET() helper returns EditableValue object'
		);

		assert.equal(
			pet.getValues( false )[0], // only non-pending values
			newVal,
			'Value has really been added'
		);

		// Test destruction:
		pet.destroy();

		QUnit.assert.equal(
			pet.getToolbar(),
			null,
			'destroyed toolbar'
		);
	} );


	QUnit.test( 'initial check', function( assert ) {
		var $subject = $( '<div/>' ),
			pet = newTestPET( $subject );

		assert.equal(
			pet.getToolbar(),
			null,
			'No property edit tool toolbar instantiated.'
		);

		assert.equal(
			pet._getToolbarParent()[0],
			$subject[0],
			'placed property edit tool in DOM'
		);

		assert.ok(
			pet.getValues() instanceof Array,
			'editable values of property edit tool initiated correctly'
		);

		assert.ok(
			!pet.isFull(),
			'is not full'
		);

		assert.ok(
			!pet.isInEditMode(),
			'is not in edit mode'
		);

		assert.ok(
			!pet.isInAddMode(),
			'is not in add mode'
		);

		assert.equal(
			pet._getValueElems().length,
			0,
			'has no elements with values'
		);

		pet.destroy();

		assert.equal(
			pet.getSubject().children().length,
			0,
			'cleaned DOM of property edit tool'
		);
	} );


	QUnit.test( 'editable values', function( assert ) {
		var pet = newTestPET( $( '<div/>' ) );

		assert.ok(
			pet._initSingleValue(
				$( '<div><div class="wb-value"></div></div>' )
			) instanceof wb.ui.PropertyEditTool.EditableValue,
			'initiated editable value component'
		);

		assert.equal(
			pet._editableValues.length,
			1,
			'stored editable value'
		);

		assert.ok(
			pet._editableValues[0]._toolbar instanceof $.wikibase.toolbar,
			'instantiated toolbar for editable value'
		);

		assert.ok(
			pet._editableValues[0]._toolbar.$editGroup.data( 'toolbareditgroup' )
				instanceof $.wikibase.toolbareditgroup,
			'instantiated edit group for editable value toolbar'
		);

		assert.equal(
			pet.getIndexOf( pet._editableValues[0] ),
			0,
			'checked index of editable value'
		);

		assert.ok(
			pet.getValues().length === pet.getValues( true ).length,
			'checked getValues()'
		);

		assert.ok(
			pet.enterNewValue( '' ) instanceof wb.ui.PropertyEditTool.EditableValue,
			'instantiated editable value for entering a new value'
		);

		assert.equal(
			pet.getValues().length,
			1,
			'one value that is not pending'
		);

		assert.equal(
			pet.getValues( true ).length,
			2,
			'two values including pending values'
		);

		assert.equal(
			pet.isInAddMode(),
			true,
			'is in add mode'
		);

		assert.equal(
			pet.isInEditMode(),
			true,
			'is in edit mode'
		);

		pet.setOption( 'allowsMultipleValues', true );
		assert.ok(
			!pet.isFull(),
			'is not full when using multiple values option'
		);

		pet.setOption( 'allowsMultipleValues', false );
		assert.ok(
			pet.isFull(),
			'is full when not using multiple values option'
		);

		assert.equal(
			pet.getSubject().children().length,
			1,
			'checked DOM'
		);

	} );


	QUnit.test( 'multiple PropertyEditTools', function( assert ) {
		var pet1 = newTestPET( $( '<div/>' ) ),
			pet2 = newTestPET( $( '<div/>' ) );

		addValueToPET( pet1, 'foo' );
		addValueToPET( pet2, 'baa' );

		assert.equal(
			pet1.isEnabled(),
			true,
			'1st edit tool is enabled'
		);

		assert.equal(
			pet2.isEnabled(),
			true,
			'2nd edit tool is enabled'
		);

		assert.equal(
			pet1.getValues()[0].startEditing(),
			true,
			'started edit mode for 1st edit tool'
		);

		assert.equal(
			pet1.getSubject().hasClass( pet1.UI_CLASS + '-ineditmode' ),
			true,
			'highlighted 1st property edit tool'
		);

		assert.equal(
			pet2.getSubject().hasClass( pet2.UI_CLASS + '-ineditmode' ),
			false,
			'2nd property is not highlighted'
		);

		assert.equal(
			pet1.isEnabled(),
			true,
			'1st edit tool is still enabled'
		);

		assert.equal(
			pet2.isDisabled(),
			true,
			'2nd edit tool is disabled'
		);

		assert.equal(
			pet2.isEnabled(),
			false,
			'2nd edit tool is not enabled'
		);

		pet1.getValues()[0].stopEditing();

		assert.equal(
			pet2.isEnabled(),
			true,
			'2nd edit tool is enabled'
		);

		assert.equal(
			pet1.getSubject().hasClass( pet1.UI_CLASS + '-ineditmode' ),
			false,
			'removed highlight on 1st property edit tool'
		);

		assert.equal(
			pet2.getSubject().hasClass( pet2.UI_CLASS + '-ineditmode' ),
			false,
			'2nd property is not highlighted'
		);

		assert.equal(
			pet1.isEnabled(),
			true,
			'1st edit tool is enabled'
		);

	} );


}( mediaWiki, wikibase, jQuery, QUnit ) );
