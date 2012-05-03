/**
 * QUnit tests for site links edit tool
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file wikibase.ui.SiteLinksEditTool.tests.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
'use strict';

( function() {
	module( 'wikibase.ui.SiteLinksEditTool', {
		setup: function() {
			// get empty nodes we get when no links on the site yet:
			var dom = window.wikibase.ui.SiteLinksEditTool.getEmptyStructure();

			// initialize:
			this.subject = new window.wikibase.ui.SiteLinksEditTool( dom );

			ok(
				this.subject._editableValues instanceof Array,
				'editable values initiated correctly'
			);

		},
		teardown: function() {
			this.subject.destroy();

			equal(
				this.subject._editableValues,
				null,
				'destroyed editable values'
			);

			this.subject = null;
		}

	} );


	test( 'adding a new editable site link', function() {

		var initialValue = [ 'de', 'Berlin' ];
		var newValue = this.subject.enterNewValue( initialValue );

		equal(
			this.subject.getValues().length,
			0,
			'getValues() should return no elements since the new one is still pending'
		)

		equal(
			this.subject.getValues( true ).length,
			1,
			'getValues( true ) should return the pending element'
		)

		ok(
			typeof ( this.subject.getValues( true )[0] ) == 'object', // same as newValue
			'newly inserted value returned by enterNewValue( value )'
		);

		ok(
			newValue instanceof window.wikibase.ui.PropertyEditTool.EditableSiteLink
			&& newValue instanceof this.subject.getEditableValuePrototype(),
			'editable values have the right prototype'
		);

		ok(
			window.wikibase.ui.PropertyEditTool.EditableSiteLink.prototype.valueCompare(
				this.subject.getValues( true )[0].getValue(),
				initialValue
			),
			'new value has the value set in enterNewValue( value )'
		);

		equal(
			newValue.startEditing(),
			false,
			'start editing already active, call function again'
		)

		equal(
			newValue.stopEditing( true ),
			true,
			'stopped editing (save), true returned because value has changed (it was created)'
		)

	} );


}() );
