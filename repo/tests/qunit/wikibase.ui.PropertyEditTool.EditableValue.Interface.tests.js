/**
 * QUnit tests for input interface component of property edit tool
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
	module( 'wikibase.ui.PropertyEditTool.EditableValue.Interface', QUnit.newMwEnvironment() );

	var node = $( '<div/>', { id: 'subject' } );
	var evInterface = new window.wikibase.ui.PropertyEditTool.EditableValue.Interface( node );

	var strings = {
		valid: [ 'test', 'test 2' ],
		invalid: [ '' ]
	};

	var setup = null;


	test( 'setup', function() {

		equal(
			evInterface._subject.length,
			1,
			'has subject'
		);

		ok(
			evInterface._subject[0] == node[0],
			'validated subject'
		);

		ok(
			evInterface._getValueContainer()[0] == node[0],
			'validated subject as container'
		);

		equal(
			evInterface.isInEditMode(),
			false,
			'not in edit mode'
		);

		equal(
			evInterface.isEmpty(),
			true,
			'value is empty'
		);

		equal(
			evInterface.isValid(),
			false,
			'input invalid'
		);

		equal(
			evInterface.isActive(),
			true,
			'is active'
		);

		equal(
			evInterface.validate( strings['invalid'][0] ),
			false,
			'empty value would be invalid'
		);

		equal(
			evInterface.validate( strings['valid'][0] ),
			true,
			'some string would be valid'
		);

		evInterface.destroy();

		equal(
			$( evInterface._getValueContainer()[0] ).children().length,
			0,
			'no input element'
		);

	} );


	test( 'edit', function() {

		equal(
			evInterface.startEditing(),
			true,
			'start editing'
		);

		equal(
			evInterface.isInEditMode(),
			true,
			'is in edit mode'
		);

		ok(
			$( evInterface._getValueContainer()[0] ).children()[0] == evInterface._inputElem[0],
			'attached input element to subject node'
		);

		evInterface.setValue( strings['valid'][0] );

		ok(
			evInterface.getValue() == strings['valid'][0],
			'value change'
		);

		equal(
			evInterface.isEmpty(),
			false,
			'input is not empty'
		);

		equal(
			evInterface.isValid(),
			true,
			'input is valid'
		);

		equal(
			evInterface.stopEditing(),
			false,
			'stop editing'
		);

		equal(
			$( evInterface._getValueContainer()[0] ).children().length,
			0,
			'removed input element'
		);

		evInterface.setValue( strings['valid'][1] );

		equal(
			evInterface.startEditing(),
			true,
			'start editing'
		);

		evInterface.setValue( strings['valid'][0] );

		ok(
			evInterface.getValue() == strings['valid'][0],
			'value change'
		);

		ok(
			evInterface.getInitialValue() == strings['valid'][1],
			'validating initial value'
		);

		evInterface.destroy();

		equal(
			$( evInterface._getValueContainer()[0] ).children().length,
			0,
			'no input element'
		);

	} );


	test( 'state changes', function() {

		equal(
			evInterface.isActive(),
			true,
			'is active'
		);

		equal(
			evInterface.isInEditMode(),
			false,
			'is in edit mode'
		);

		equal(
			evInterface.startEditing(),
			true,
			'start editing'
		);

		evInterface.setDisabled( true );
		equal(
			evInterface.isDisabled(),
			true,
			'disable'
		);
		ok(
			evInterface._inputElem.attr( 'disabled' ),
			true,
			'input element is disabled'
		);

		evInterface.setDisabled( false );
		equal(
			evInterface.isDisabled(),
			false,
			'enabled'
		);
		ok(
			typeof evInterface._inputElem.attr( 'disabled' ) == 'undefined',
			'input element is not disabled'
		);

		evInterface.setActive( false );
		equal(
			evInterface.isActive(),
			false,
			'deactivated'
		);

		equal(
			evInterface.isInEditMode(),
			false,
			'is not in edit mode'
		);

		equal(
			$( evInterface._getValueContainer()[0] ).children().length,
			0,
			'removed input element'
		);

		evInterface.setActive( true );
		equal(
			evInterface.isActive(),
			true,
			'activated'
		);

		evInterface.destroy();

		equal(
			$( evInterface._getValueContainer()[0] ).children().length,
			0,
			'no input element'
		);

	} );


}() );
