/**
 * QUnit tests for property edit tool
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file wikibase.ui.PropertyEditTool.tests.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author H. Snater
 */
'use strict';


( function() {
	module( 'wikibase.ui.PropertyEditTool', window.QUnit.newWbEnvironment( {
		setup: function() {
			this.node = $( '<div/>' );
			this.propertyEditTool = new window.wikibase.ui.PropertyEditTool( this.node );

			ok(
				this.propertyEditTool._toolbar instanceof window.wikibase.ui.Toolbar,
				'instantiated toolbar'
			);

			equal(
				this.propertyEditTool._getToolbarParent().html(),
				this.node.html(),
				'placed in DOM'
			);

			ok(
				this.propertyEditTool._editableValues instanceof Array,
				'editable values initiated correctly'
			);

		},
		teardown: function() {
			this.propertyEditTool.destroy();

			equal(
				this.propertyEditTool._editableValues,
				null,
				'destroyed editable values'
			);

			equal(
				this.propertyEditTool._subject.children().length,
				0,
				'cleaned DOM'
			);

			this.node = null;
			this.propertyEditTool = null;
		}

	} ) );

	test( 'initial check', function() {

		equal(
			this.propertyEditTool.isFull(),
			true,
			'is full'
		);

		equal(
			this.propertyEditTool.isInEditMode(),
			false,
			'is not in edit mode'
		);

		equal(
			this.propertyEditTool.isInAddMode(),
			false,
			'is not in add mode'
		);

		equal(
			this.propertyEditTool._getValueElems().length,
			0,
			'has no elements with values'
		);

	} );


	test( 'editable values', function() {

		ok(
			this.propertyEditTool._initSingleValue( $( '<div/>' ) ) instanceof window.wikibase.ui.PropertyEditTool.EditableValue,
			'initiated editable value component'
		);

		equal(
			this.propertyEditTool._editableValues.length,
			1,
			'stored editable value'
		);

		ok(
			this.propertyEditTool._editableValues[0]._toolbar instanceof window.wikibase.ui.Toolbar,
			'instantiated toolbar for editable value'
		);

		ok(
			this.propertyEditTool._editableValues[0]._toolbar.editGroup instanceof window.wikibase.ui.Toolbar.EditGroup,
			'instantiated edit group for editable value toolbar'
		);

		equal(
			this.propertyEditTool.getIndexOf( this.propertyEditTool._editableValues[0] ),
			0,
			'checked index of editable value'
		);

		ok(
			this.propertyEditTool.getValues().length == this.propertyEditTool.getValues( true ).length,
			'checked getValues()'
		);

		ok(
			this.propertyEditTool.enterNewValue() instanceof window.wikibase.ui.PropertyEditTool.EditableValue,
			'instantiated editable value for entering a new value'
		);

		equal(
			this.propertyEditTool.getValues().length,
			1,
			'one value that is not pending'
		);

		equal(
			this.propertyEditTool.getValues( true ).length,
			2,
			'two values including pending values'
		);

		equal(
			this.propertyEditTool.isInAddMode(),
			true,
			'is in add mode'
		);

		equal(
			this.propertyEditTool.isInEditMode(),
			true,
			'is in edit mode'
		);

		equal(
			this.propertyEditTool.isFull(),
			true,
			'is full'
		);

		this.propertyEditTool.allowsMultipleValues = false;

		equal(
			this.propertyEditTool.isFull(),
			false,
			'is not full'
		);

		equal(
			this.propertyEditTool._subject.children().length,
			2,
			'checked DOM'
		);

	} );


}() );
