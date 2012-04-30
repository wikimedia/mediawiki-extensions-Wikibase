/**
 * QUnit tests for editable value component of property edit tool
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file wikibase.ui.PropertyEditTool.EditableValue.tests.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author H. Snater
 */
"use strict";

( function () {
	module( 'wikibase.ui.PropertyEditTool.EditableValue', QUnit.newMwEnvironment() );

	var node = $( '<div/>', { id: 'subject' } );
	$( '<div/>', { id: 'parent' } ).append( node );
	var propertyEditTool = new window.wikibase.ui.PropertyEditTool( node );
	var editableValue = new window.wikibase.ui.PropertyEditTool.EditableValue;
	var toolbar = propertyEditTool._buildSingleValueToolbar( editableValue );
	editableValue._init( node, toolbar );

	var strings = {
		valid: [ 'test', 'test 2' ],
		invalid: [ '' ]
	};


	test( 'setup', function() {

		equal(
			editableValue._getToolbarParent().attr( 'id' ),
			'parent',
			'parent node for toolbar exists'
		);

		ok(
			editableValue._interfaces.length == 1
				&& editableValue._interfaces[0] instanceof window.wikibase.ui.PropertyEditTool.EditableValue.Interface,
			'initialized one interface'
		);

		equal(
			editableValue.getInputHelpMessage(),
			'',
			'checked help message'
		);

		equal(
			editableValue.isPending(),
			false,
			'value is not pending'
		);

		equal(
			editableValue.isInEditMode(),
			false,
			'not in edit mode'
		);

	} );


	test( 'edit', function() {

		equal(
			editableValue.startEditing(),
			true,
			'started edit mode'
		);

		equal(
			editableValue.isInEditMode(),
			true,
			'is in edit mode'
		);

		editableValue.setValue( strings['valid'][0] );

		ok(
			editableValue.getValue() instanceof Array && editableValue.getValue()[0] == strings['valid'][0],
			'changed value'
		);

		equal(
			editableValue.stopEditing(),
			false,
			'stopped edit mode'
		);

		equal(
			editableValue.isInEditMode(),
			false,
			'is not in edit mode'
		);

		editableValue.setValue( strings['valid'][1] );

		ok(
			editableValue.getValue() instanceof Array && editableValue.getValue()[0] == strings['valid'][1],
			'changed value'
		);

		equal(
			editableValue.startEditing(),
			true,
			'started edit mode'
		);

		equal(
			editableValue.validate( [strings['invalid'][0]] ),
			false,
			'empty value not validated'
		);

		equal(
			editableValue.validate( [strings['valid'][0]] ),
			true,
			'validated input'
		);

		editableValue.setValue( strings['invalid'][0] );

		ok(
			editableValue.getValue() instanceof Array && editableValue.getValue()[0] == strings['invalid'][0],
			'set empty value'
		);

		equal(
			editableValue.isEmpty(),
			true,
			'editable value is empty'
		);

		ok(
			editableValue.getValue() instanceof Array && editableValue.getInitialValue()[0] == strings['valid'][1],
			'checked initial value'
		);

		equal(
			editableValue.valueCompare( editableValue.getValue(), editableValue.getInitialValue() ),
			false,
			'compared current and initial value'
		);

		editableValue.setValue( strings['valid'][1] );

		ok(
			editableValue.getValue() == strings['valid'][1],
			'reset value to initial value'
		);

		equal(
			editableValue.valueCompare( editableValue.getValue(), editableValue.getInitialValue() ),
			true,
			'compared current and initial value'
		);

	} );


	test( 'destroy', function() {

		editableValue.destroy();

		equal(
			editableValue._toolbar,
			null,
			'destroyed toolbar'
		);

		equal(
			editableValue._instances,
			null,
			'destroyed instances'
		);

	} );


}() );
