/**
 * QUnit tests for Toolbar.Label prototype
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 * @author H. Snater < mediawiki at snater.com >
 */

( function( wb, $, QUnit, undefined ) {
	'use strict';

	/**
	 * Factory for creating a new Toolbar.Label suited for testing.
	 *
	 * @param {String} [text] label text
	 * @return {wb.ui.Toolbar.Label}
	 */
	var newTestLabel = function( text ) {
		if ( text === undefined ) {
			text = 'Text';
		}
		return new wb.ui.Toolbar.Label( text );
	};

	QUnit.module( 'wikibase.ui.Toolbar.Label', QUnit.newWbEnvironment() );

	QUnit.test( 'Set and get content', function( assert ) {
		var label = newTestLabel( 'Text' );

		assert.ok(
			( typeof label._elem === 'object' ) && label.getContent() === 'Text',
			'Label was initialized properly'
		);

		label.setContent( 'Foo' );

		assert.equal(
			label.getContent(),
			'Foo',
			'Content equals the content set before'
		);

		var jQueryObj = $( '<span/>' );
		label.setContent( jQueryObj );

		assert.equal(
			label.getContent()[0],
			jQueryObj[0], // compare with containing node
			'Content equals the content set before'
		);

		label.destroy();

		assert.equal(
			label._elem,
			null,
			'destroyed label'
		);
	} );

	QUnit.test( 'Disable and enable', function( assert ) {
		var label = newTestLabel();

		assert.equal(
			label.isDisabled(),
			false,
			'not yet disabled'
		);

		assert.equal(
			label.disable(),
			true,
			'disable, state changed'
		);

		assert.equal(
			label.isDisabled(),
			true,
			'disabled'
		);

		assert.equal(
			label.disable(),
			true,
			'disabling one more'
		);

		assert.equal(
			label.isDisabled(),
			true,
			'disabled'
		);

		assert.equal(
			label.enable(),
			true,
			'enable, state changed'
		);

		assert.equal(
			label.isDisabled(),
			false,
			'enabled'
		);

		assert.equal(
			label.enable(),
			true,
			'enabling once more'
		);

		assert.equal(
			label.isDisabled(),
			false,
			'enabled'
		);

		label.stateChangeable = false;

		assert.equal(
			label.disable(),
			true,
			'trying to disable without state being changeable'
		);

		assert.equal(
			label.isDisabled(),
			false,
			'state did not change'
		);
	} );

	QUnit.test( 'EVENTS: beforeDisable and beforeEnable', function( assert ) {
		var label = newTestLabel();

		// set events:
		label.beforeDisable = function() { return false; };
		label.beforeEnable = function() { return false; };

		assert.equal(
			label.disable(),
			false,
			'event beforeDisabled return value will cancel disable command'
		);

		assert.equal(
			label.isDisabled(),
			false,
			'still enabled'
		);

		label.beforeDisable = function() { return true; };

		assert.equal(
			label.disable(),
			true,
			'event beforeDisabled removed, disabled'
		);

		assert.equal(
			label.enable(),
			false,
			'try to enable, beforeEnabled will prevent'
		);

		assert.equal(
			label.isDisabled(),
			true,
			'still disabled'
		);

		label.beforeEnable = function() { return true; };

		assert.equal(
			label.enable(),
			true,
			'event beforeEnable removed, enable'
		);

		assert.equal(
			label.isDisabled(),
			false,
			'enabled'
		);
	} );

}( wikibase, jQuery, QUnit ) );
