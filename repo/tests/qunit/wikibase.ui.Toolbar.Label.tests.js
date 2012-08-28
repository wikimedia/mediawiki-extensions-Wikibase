/**
 * QUnit tests for Label prototype for toolbars
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 * @author H. Snater
 */
'use strict';

( function () {
	module( 'wikibase.ui.Toolbar.Label', window.QUnit.newWbEnvironment( {
		setup: function() {

			this.label = new window.wikibase.ui.Toolbar.Label( 'Text' );

			ok(
				( typeof this.label._elem == 'object' ) && this.label.getContent() == 'Text',
				'Label was initialized properly'
			);

		},
		teardown: function() {
			this.label.destroy();

			equal(
				this.label._elem,
				null,
				'destroyed label'
			);
		}

	} ) );

	test( 'set and get content', function() {

		this.label.setContent( 'Foo' );

		equal(
			this.label.getContent(),
			'Foo',
			'Content equals the content set before'
		);

		var jQueryObj = $( '<span/>' );
		this.label.setContent( jQueryObj );

		equal(
			this.label.getContent()[0],
			jQueryObj[0], // compare with containing node
			'Content equals the content set before'
		);

	} );

	test( 'disable and enable', function() {

		equal(
			this.label.isDisabled(),
			false,
			'not yet disabled'
		);

		equal(
			this.label.setDisabled(),
			true,
			'disable, state changed'
		);

		equal(
			this.label.isDisabled(),
			true,
			'disabled'
		);

		equal(
			this.label.setDisabled( true ),
			true,
			'disabling one more'
		);

		equal(
			this.label.isDisabled(),
			true,
			'disabled'
		);

		equal(
			this.label.setDisabled( false ),
			true,
			'enable, state changed'
		);

		equal(
			this.label.isDisabled(),
			false,
			'enabled'
		);

		equal(
			this.label.setDisabled( false ),
			true,
			'enabling once more'
		);

		equal(
			this.label.isDisabled(),
			false,
			'enabled'
		);

		this.label.stateChangeable = false;

		equal(
			this.label.setDisabled( true ),
			true,
			'trying to disable without state being changeable'
		);

		equal(
			this.label.isDisabled(),
			false,
			'state did not change'
		);

	} );

	test( 'EVENTS: beforeDisable and beforeEnable', function() {
		// set events:
		this.label.beforeDisable = function() {	return false; };
		this.label.beforeEnable = function() {	return false; };

		equal(
			this.label.setDisabled(),
			false,
			'event beforeDisabled return value will cancel disable command'
		);

		equal(
			this.label.isDisabled(),
			false,
			'still enabled'
		);

		this.label.beforeDisable = function() {	return true; };

		equal(
			this.label.setDisabled(),
			true,
			'event beforeDisabled removed, disabled'
		);

		equal(
			this.label.setDisabled( false ),
			false,
			'try to enable, beforeEnabled will prevent'
		);

		equal(
			this.label.isDisabled(),
			true,
			'still disabled'
		);

		this.label.beforeEnable = function() {	return true; };

		equal(
			this.label.setDisabled( false ),
			true,
			'event beforeEnable removed, enable'
		);

		equal(
			this.label.isDisabled(),
			false,
			'enabled'
		);

	} );

}() );
