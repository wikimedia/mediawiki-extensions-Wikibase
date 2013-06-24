/**
 * QUnit tests for PropertyEditTool.EditableValue's generic interface component
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author H. Snater <mediawiki@snater.com>
 */

( function( wb, $, QUnit, undefined ) {
	'use strict';

	/**
	 * Factory for creating a new Interface object suited for testing.
	 *
	 * @return  wb.ui.PropertyEditTool.EditableValue.Interface
	 */
	var newTestInterface = function( $node ) {
		if ( $node === undefined ) {
			$node = $( '<div/>', { id: 'subject' } );
		}
		return new wb.ui.PropertyEditTool.EditableValue.Interface( $node );
	};

	QUnit.module( 'wikibase.ui.PropertyEditTool.EditableValue.Interface', QUnit.newWbEnvironment( {
		setup: function() {
			this.strings = {
				valid: [ 'test', 'test 2' ],
				invalid: [ '' ]
			};
			this.language = {
				rtl: {
					code: 'fakertllang',
					dir: 'rtl'
				},
				ltr: {
					code: 'fakeltrlang',
					dir: 'ltr'
				}
			};
		},
		teardown: function() {}
	} ) );


	QUnit.test( 'initial check', function( assert ) {

		var $node = $( '<div/>', { id: 'subject' } );
		var subject = newTestInterface( $node );

		assert.equal(
			subject.getSubject().length,
			1,
			'has subject'
		);

		assert.ok(
			subject.getSubject()[0] === $node[0],
			'validated subject'
		);

		assert.ok(
			subject._getValueContainer()[0] === $node[0],
			'validated subject as container'
		);

		assert.equal(
			subject.isInEditMode(),
			false,
			'not in edit mode'
		);

		assert.equal(
			subject.isEmpty(),
			true,
			'value is empty'
		);

		assert.equal(
			subject.isValid(),
			false,
			'input invalid'
		);

		assert.equal(
			subject.isActive(),
			true,
			'is active'
		);

		assert.equal(
			subject.validate( this.strings.invalid[0] ),
			false,
			'empty value would be invalid'
		);

		assert.equal(
			subject.validate( this.strings.valid[0] ),
			true,
			'some string would be valid'
		);

		subject.destroy();

		assert.equal(
			$( subject._getValueContainer()[0] ).children().length,
			0,
			'no input element'
		);

		subject.destroy();

		assert.equal(
			$( subject._getValueContainer()[0] ).children().length,
			0,
			'no input element'
		);

	} );


	QUnit.test( 'edit', function( assert ) {

		var subject = newTestInterface();

		assert.equal(
			subject.startEditing(),
			true,
			'start editing'
		);

		assert.equal(
			subject.isInEditMode(),
			true,
			'is in edit mode'
		);

		assert.ok(
			$( subject._getValueContainer()[0] ).children()[0] === subject._inputElem[0],
			'attached input element to subject node'
		);

		subject.setValue( this.strings.valid[0] );

		assert.ok(
			subject.getValue() === this.strings.valid[0],
			'value change'
		);

		assert.equal(
			subject.isEmpty(),
			false,
			'input is not empty'
		);

		assert.equal(
			subject.isValid(),
			true,
			'input is valid'
		);

		assert.equal(
			subject.stopEditing(),
			false,
			'stop editing'
		);

		assert.equal(
			$( subject._getValueContainer()[0] ).children().length,
			0,
			'removed input element'
		);

		subject.setValue( this.strings.valid[1] );

		assert.equal(
			subject.startEditing(),
			true,
			'start editing'
		);

		subject.setValue( this.strings.valid[0] );

		assert.ok(
			subject.getValue() === this.strings.valid[0],
			'value change'
		);

		assert.ok(
			subject.getInitialValue() === this.strings.valid[1],
			'validating initial value'
		);

		subject.destroy();

		assert.equal(
			$( subject._getValueContainer()[0] ).children().length,
			0,
			'no input element'
		);

	} );


	QUnit.test( 'state changes', function( assert ) {

		var subject = newTestInterface();

		assert.equal(
			subject.isActive(),
			true,
			'is active'
		);

		assert.equal(
			subject.isInEditMode(),
			false,
			'is in edit mode'
		);

		assert.equal(
			subject.startEditing(),
			true,
			'start editing'
		);

		assert.equal(
			subject.disable(),
			true,
			'disable'
		);

		assert.equal(
			subject.isDisabled(),
			true,
			'disabled'
		);

		assert.ok(
			subject._inputElem.attr( 'disabled' ),
			true,
			'input element is disabled'
		);

		assert.equal(
			subject.enable(),
			true,
			'enable'
		);

		assert.equal(
			subject.isDisabled(),
			false,
			'enabled'
		);

		assert.ok(
			subject._inputElem.attr( 'disabled' ) === undefined,
			'input element is not disabled'
		);

		subject.setActive( false );
		assert.equal(
			subject.isActive(),
			false,
			'deactivated'
		);

		assert.equal(
			subject.isInEditMode(),
			false,
			'is not in edit mode'
		);

		assert.equal(
			$( subject._getValueContainer()[0] ).children().length,
			0,
			'removed input element'
		);

		subject.setActive( true );
		assert.equal(
			subject.isActive(),
			true,
			'activated'
		);

	} );


	QUnit.test( 'update language attributes', function( assert ) {

		var subject = newTestInterface();
		subject.setLanguageAttributes( this.language.ltr );

		assert.equal(
			subject.getSubject().attr( 'lang' ),
			this.language.ltr.code,
			'assign ltr language code to subject'
		);

		assert.equal(
			subject.getSubject().attr( 'dir' ),
			this.language.ltr.dir,
			'assign ltr language direction to subject'
		);

		subject.setLanguageAttributes( this.language.rtl );

		assert.equal(
			subject.getSubject().attr( 'lang' ),
			this.language.rtl.code,
			'assign rtl language code to subject'
		);

		assert.equal(
			subject.getSubject().attr( 'dir' ),
			this.language.rtl.dir,
			'assign rtl language direction to subject'
		);

		assert.equal(
			subject.startEditing(),
			true,
			'start editing'
		);

		assert.equal(
			subject._inputElem.attr( 'lang' ),
			this.language.rtl.code,
			'input has rtl language'
		);

		assert.equal(
			subject._inputElem.attr( 'dir' ),
			this.language.rtl.dir,
			'input has rtl direction'
		);

		subject.setLanguageAttributes( this.language.ltr );

		assert.equal(
			subject._inputElem.attr( 'lang' ),
			this.language.ltr.code,
			'input has ltr language'
		);

		assert.equal(
			subject._inputElem.attr( 'dir' ),
			this.language.ltr.dir,
			'input has ltr direction'
		);

		assert.equal(
			subject.stopEditing(),
			false,
			'stop editing'
		);

		assert.equal(
			subject.getSubject().attr( 'lang' ),
			this.language.ltr.code,
			'subject has ltr language code'
		);

		assert.equal(
			subject.getSubject().attr( 'dir' ),
			this.language.ltr.dir,
			'subject has ltr direction'
		);

	} );


}( wikibase, jQuery, QUnit ) );
