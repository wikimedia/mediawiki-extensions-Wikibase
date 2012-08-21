/**
 * QUnit tests for input interface component of property edit tool
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author H. Snater
 */
'use strict';


( function () {
	module( 'wikibase.ui.PropertyEditTool.EditableValue.Interface', window.QUnit.newWbEnvironment( {
		setup: function() {
			this.node = $( '<div/>', { id: 'subject' } );
			this.evInterface = new window.wikibase.ui.PropertyEditTool.EditableValue.Interface( this.node );
			this.strings = {
				valid: [ 'test', 'test 2' ],
				invalid: [ '' ]
			};

			equal(
				this.evInterface._subject.length,
				1,
				'has subject'
			);

			ok(
				this.evInterface._subject[0] == this.node[0],
				'validated subject'
			);

			ok(
				this.evInterface._getValueContainer()[0] == this.node[0],
				'validated subject as container'
			);

		},
		teardown: function() {
			this.evInterface.destroy();

			equal(
				$( this.evInterface._getValueContainer()[0] ).children().length,
				0,
				'no input element'
			);

			this.evInterface = null;
			this.node = null;
			this.strings = null;
		}
	} ) );


	test( 'initial check', function() {

		equal(
			this.evInterface.isInEditMode(),
			false,
			'not in edit mode'
		);

		equal(
			this.evInterface.isEmpty(),
			true,
			'value is empty'
		);

		equal(
			this.evInterface.isValid(),
			false,
			'input invalid'
		);

		equal(
			this.evInterface.isActive(),
			true,
			'is active'
		);

		equal(
			this.evInterface.validate( this.strings['invalid'][0] ),
			false,
			'empty value would be invalid'
		);

		equal(
			this.evInterface.validate( this.strings['valid'][0] ),
			true,
			'some string would be valid'
		);

		this.evInterface.destroy();

		equal(
			$( this.evInterface._getValueContainer()[0] ).children().length,
			0,
			'no input element'
		);

	} );


	test( 'edit', function() {

		equal(
			this.evInterface.startEditing(),
			true,
			'start editing'
		);

		equal(
			this.evInterface.isInEditMode(),
			true,
			'is in edit mode'
		);

		ok(
			$( this.evInterface._getValueContainer()[0] ).children()[0] == this.evInterface._inputElem[0],
			'attached input element to subject node'
		);

		this.evInterface.setValue( this.strings['valid'][0] );

		ok(
			this.evInterface.getValue() == this.strings['valid'][0],
			'value change'
		);

		equal(
			this.evInterface.isEmpty(),
			false,
			'input is not empty'
		);

		equal(
			this.evInterface.isValid(),
			true,
			'input is valid'
		);

		equal(
			this.evInterface.stopEditing(),
			false,
			'stop editing'
		);

		equal(
			$( this.evInterface._getValueContainer()[0] ).children().length,
			0,
			'removed input element'
		);

		this.evInterface.setValue( this.strings['valid'][1] );

		equal(
			this.evInterface.startEditing(),
			true,
			'start editing'
		);

		this.evInterface.setValue( this.strings['valid'][0] );

		ok(
			this.evInterface.getValue() == this.strings['valid'][0],
			'value change'
		);

		ok(
			this.evInterface.getInitialValue() == this.strings['valid'][1],
			'validating initial value'
		);

		this.evInterface.destroy();

		equal(
			$( this.evInterface._getValueContainer()[0] ).children().length,
			0,
			'no input element'
		);

	} );


	test( 'state changes', function() {

		equal(
			this.evInterface.isActive(),
			true,
			'is active'
		);

		equal(
			this.evInterface.isInEditMode(),
			false,
			'is in edit mode'
		);

		equal(
			this.evInterface.startEditing(),
			true,
			'start editing'
		);

		equal(
			this.evInterface.setDisabled( true ),
			true,
			'disable'
		);

		equal(
			this.evInterface.isDisabled(),
			true,
			'disabled'
		);

		ok(
			this.evInterface._inputElem.attr( 'disabled' ),
			true,
			'input element is disabled'
		);

		equal(
			this.evInterface.setDisabled( false ),
			true,
			'enable'
		);

		equal(
			this.evInterface.isDisabled(),
			false,
			'enabled'
		);
		ok(
			typeof this.evInterface._inputElem.attr( 'disabled' ) == 'undefined',
			'input element is not disabled'
		);

		this.evInterface.setActive( false );
		equal(
			this.evInterface.isActive(),
			false,
			'deactivated'
		);

		equal(
			this.evInterface.isInEditMode(),
			false,
			'is not in edit mode'
		);

		equal(
			$( this.evInterface._getValueContainer()[0] ).children().length,
			0,
			'removed input element'
		);

		this.evInterface.setActive( true );
		equal(
			this.evInterface.isActive(),
			true,
			'activated'
		);

	} );


}() );
