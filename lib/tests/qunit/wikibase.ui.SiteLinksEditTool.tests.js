/**
 * QUnit tests for site links edit tool
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */

( function( mw, wb, $, QUnit, undefined ) {
	'use strict';

	var config = {
		'wbSiteDetails': {
			en: {
				apiUrl: 'http://en.wikipedia.org/w/api.php',
				id: 'en',
				name: 'English Wikipedia',
				pageUrl: 'http://en.wikipedia.org/wiki/$1',
				shortName: 'English',
				languageCode: 'en',
				globalSiteIt: 'enwiki'
			},
			de: {
				apiUrl: 'http://de.wikipedia.org/w/api.php',
				id: 'de',
				name: 'Deutsche Wikipedia',
				pageUrl: 'http://de.wikipedia.org/wiki/$1',
				shortName: 'Deutsch',
				languageCode: 'de',
				globalSiteId: 'dewiki'
			}
		}
	};

	QUnit.module( 'wikibase.ui.SiteLinksEditTool', QUnit.newWbEnvironment( {
		config: config,
		setup: function() {

			this.apiResponse = {
				entity: { sitelinks: { dewiki: { title: 'ein_titel' } } }
			};

			// get empty nodes we get when no links on the site yet:
			var dom = wb.ui.SiteLinksEditTool.getEmptyStructure();

			// initialize:
			this.subject = new wb.ui.SiteLinksEditTool( dom );
		},
		teardown: function() {}
	} ) );


	QUnit.test( 'adding a new editable site link', function( assert ) {

		assert.ok(
			this.subject._editableValues instanceof Array,
			'editable values initiated correctly'
		);

		var initialValue = [ 'Deutsch (de)', 'Berlin' ];
		var newValue = this.subject.enterNewValue( initialValue );

		// override AJAX API call
		this.subject._editableValues[0].triggerApi = function( deferred, apiAction ) {
			deferred.resolve( {} );
		};

		// set result set for validation
		this.subject._editableValues[0].sitePageInterface.setResultSet( ['Berlin'] );

		// pretend API success
		newValue.triggerApi = $.proxy( function( deferred, apiAction ) {
			deferred.resolve( this.apiResponse );
		}, this );

		assert.equal(
			this.subject.getValues().length,
			0,
			'getValues() should return no elements since the new one is still pending'
		);

		assert.equal(
			this.subject.getValues( true ).length,
			1,
			'getValues( true ) should return the pending element'
		);

		assert.ok(
			typeof ( this.subject.getValues( true )[0] ) === 'object', // same as newValue
			'newly inserted value returned by enterNewValue( value )'
		);

		assert.ok(
			newValue instanceof wb.ui.PropertyEditTool.EditableSiteLink
			&& newValue instanceof this.subject.getEditableValuePrototype(),
			'editable values have the right prototype'
		);

		assert.ok(
			newValue.valueCompare(
				this.subject.getValues( true )[0].getValue(),
				['de', 'Berlin']
			),
			'new value has the value set in enterNewValue( value )'
		);

		assert.equal(
			newValue.startEditing(),
			false,
			'start editing already active, call function again'
		);

		assert.equal(
			newValue.stopEditing( true ).promisor.apiAction,
			newValue.API_ACTION.SAVE,
			'stopped editing (save), true returned because value has changed (it was created)'
		);

		assert.equal(
			this.subject.enterNewValue().siteIdInterface.getResultSetMatch( initialValue[0] ),
			null,
			'The site id set already shouldn\'t be available in the set of suggestions anymore'
		);

		this.subject.destroy();

		assert.equal(
			this.subject._editableValues,
			null,
			'destroyed editable values'
		);

	} );


}( mediaWiki, wikibase, jQuery, QUnit ) );
