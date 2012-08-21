/**
 * QUnit tests for site links edit tool
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
'use strict';

( function() {

	var config = {
		'wbSiteDetails': {
			en: {
				apiUrl: 'http://en.wikipedia.org/w/api.php',
				id: 'en',
				name: 'English Wikipedia',
				pageUrl: 'http://en.wikipedia.org/wiki/$1',
				shortName: 'English'
			},
			de: {
				apiUrl: 'http://de.wikipedia.org/w/api.php',
				id: 'de',
				name: 'Deutsche Wikipedia',
				pageUrl: 'http://de.wikipedia.org/wiki/$1',
				shortName: 'Deutsch'
			}
		}
	};

	module( 'wikibase.ui.SiteLinksEditTool', window.QUnit.newWbEnvironment( {
		config: config,
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
	} ) );


	test( 'adding a new editable site link', function() {

		var initialValue = [ 'Deutsch (de)', 'Berlin' ];
		var newValue = this.subject.enterNewValue( initialValue );
		this.subject._editableValues[0].queryApi = function( deferred, apiAction ) { // override AJAX API call
			deferred.resolve();
		};
		this.subject._editableValues[0].pageNameInterface.setResultSet( ['Berlin'] ); // set result set for validation

		newValue.queryApi = function( deferred, apiAction ) { // pretend API success
			deferred.resolve();
		};

		equal(
			this.subject.getValues().length,
			0,
			'getValues() should return no elements since the new one is still pending'
		);

		equal(
			this.subject.getValues( true ).length,
			1,
			'getValues( true ) should return the pending element'
		);

		ok(
			typeof ( this.subject.getValues( true )[0] ) === 'object', // same as newValue
			'newly inserted value returned by enterNewValue( value )'
		);

		ok(
			newValue instanceof window.wikibase.ui.PropertyEditTool.EditableSiteLink
			&& newValue instanceof this.subject.getEditableValuePrototype(),
			'editable values have the right prototype'
		);

		ok(
			newValue.valueCompare(
				this.subject.getValues( true )[0].getValue(),
				initialValue
			),
			'new value has the value set in enterNewValue( value )'
		);

		equal(
			newValue.startEditing(),
			false,
			'start editing already active, call function again'
		);

		equal(
			newValue.stopEditing( true ).promisor.apiAction,
			wikibase.ui.PropertyEditTool.EditableValue.prototype.API_ACTION.SAVE,
			'stopped editing (save), true returned because value has changed (it was created)'
		);

	} );


}() );
