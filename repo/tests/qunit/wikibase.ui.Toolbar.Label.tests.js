/**
 * QUnit tests for Label prototype for toolbars
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file wikibase.ui.Toolbar.Label.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
'use strict';

( function () {
	module( 'wikibase.ui.Toolbar.Label', {
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

	} );

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
			this.label.setDisabled( true ),
			false,
			'disable but disabled already, state not changed'
		);

		equal(
			this.label.isDisabled(),
			true,
			'disabled now'
		);

		equal(
			this.label.setDisabled( false ),
			true,
			'enable, state changed'
		);

		equal(
			this.label.setDisabled( false ),
			false,
			'enable but enabled already, state not changed'
		);

		equal(
			this.label.isDisabled(),
			false,
			'disabled now'
		);
	} );

	test( 'EVENTS: beforeDisable and beforeEnable', function() {
		// set events:
		this.label.beforeDisable = function() {	return false; }
		this.label.beforeEnable = function() {	return false; }

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

		this.label.beforeDisable = null;

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

		this.label.beforeEnable = null;

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
