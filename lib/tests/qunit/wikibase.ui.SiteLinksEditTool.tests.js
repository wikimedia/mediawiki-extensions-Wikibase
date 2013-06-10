/**
 * QUnit tests for site links edit tool
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */

( function( mw, wb, $, QUnit ) {
	'use strict';

	var config = {
		'wbSiteDetails': {
			enwiki: {
				apiUrl: 'http://en.wikipedia.org/w/api.php',
				name: 'English Wikipedia',
				pageUrl: 'http://en.wikipedia.org/wiki/$1',
				shortName: 'English',
				languageCode: 'en',
				id: 'enwiki',
				group: 'whatever'
			},
			dewiki: {
				apiUrl: 'http://de.wikipedia.org/w/api.php',
				name: 'Deutsche Wikipedia',
				pageUrl: 'http://de.wikipedia.org/wiki/$1',
				shortName: 'Deutsch',
				languageCode: 'de',
				id: 'dewiki',
				group: 'whatever'
			}
		}
	};

	QUnit.module( 'wikibase.ui.SiteLinksEditTool', QUnit.newWbEnvironment( {
		config: config,
		setup: function() {
			// get empty nodes we get when no links on the site yet:
			var $dom = wb.ui.SiteLinksEditTool.getEmptyStructure();

			// initialize:
			this.subject = new wb.ui.SiteLinksEditTool( $dom, {
				allowedSites: wb.getSites() // TODO: decouple test from abusing global config here
			} );
		},
		teardown: function() {
			this.subject.destroy();
		}
	} ) );

	/**
	 * Executes assertions testing how many values are currently in a certain part of the
	 * SiteLinksEditTool's subject table.
	 *
	 * @param {QUnit.assert} assert
	 * @param {wb.ui.SiteLinksEditTool} subject
	 * @param {Object} definition The Table's part (e.g. "tbody" or "tfoot" as key, the expected
	 *        number of values in that section as number.
	 * @param {string} testStepDescription
	 */
	function assertValuesInTableSections( assert, subject, definition, testStepDescription ) {
		$.each( definition, function( section, expected ) {
			var queryPath = section + ' .wb-ui-propertyedittool-editablevalue';
			assert.strictEqual(
				subject.getSubject().find( queryPath ).length,
				expected,
				expected + ' value(s) in ' + section + ' ' + testStepDescription
			);
		} );
	}

	/**
	 * Allows to enter a value into the SiteLinksEditTool which can then be saved by calling
	 * stopEditing( true ) on the returned EditableValue. This is necessary because of some ugly
	 * fake API requests have to be mocked to do this.
	 *
	 * @param editTool
	 * @param siteLink
	 * @param siteLinkGlobalId
	 * @returns {wb.ui.PropertyEditTool.EditableValue}
	 */
	function hackyValueInsertion( editTool, siteLink, siteLinkGlobalId ) {
		var initialValue = siteLink,
			newValue = editTool.enterNewValue( initialValue );

		// override AJAX API call
		newValue.triggerApi = function( deferred, apiAction ) {
			deferred.resolve( {} );
		};

		// set result set for validation
		newValue.sitePageInterface.setResultSet( siteLink[1] );

		// pretend API success
		var fakeApiResponse = { entity: { sitelinks: {} } };
		fakeApiResponse.entity.sitelinks[ siteLinkGlobalId ] = { title: 'whatever_title' };

		newValue.triggerApi = function( deferred, apiAction ) {
			deferred.resolve( fakeApiResponse );
		};
		return newValue;
	}

	/**
	 * Just like hackyValueInsertion but does the stopEditing as well.
	 *
	 * @see hackyValueInsertion
	 */
	function hackyValueInsertionAndSave( editTool, siteLink, siteLinkGlobalId ) {
		var newValue = hackyValueInsertion.apply( null, arguments );

		// TODO: stopEditing() should not fail for the 2nd value when not doing this
		var realTablesorter = $.fn.tablesorter;
		$.fn.tablesorter = $.noop;

		newValue.stopEditing( true );

		$.fn.tablesorter = realTablesorter;

		return newValue;
	}

	var enSiteLink = [ 'enwiki', 'London' ],
		deSiteLink = [ 'dewiki', 'Berlin' ];

	QUnit.test( 'getUnusedAllowedSiteIds()', function( assert ) {
		var subject = this.subject;

		assert.deepEqual(
			subject.getUnusedAllowedSiteIds(),
			[ 'enwiki', 'dewiki' ],
			'all allowed site links are returned as unused by newly initialized edit tool'
		);

		hackyValueInsertionAndSave( subject, [ 'enwiki', 'London' ], 'enwiki' );

		assert.deepEqual(
			subject.getUnusedAllowedSiteIds(),
			[ 'dewiki' ],
			'after site has been used, the site will not be returned anymore'
		);

		hackyValueInsertionAndSave( subject, [ 'dewiki', 'Berlin' ], 'dewiki' );

		assert.deepEqual(
			subject.getUnusedAllowedSiteIds(),
			[],
			'after all sites are used, an empty array will be returned'
		);
	} );

	QUnit.test( 'getUnusedAllowedSiteIds()', function( assert ) {
		var subject = this.subject;

		assert.deepEqual(
			subject.getRepresentedSiteIds(),
			[],
			'initially, no site links, so no sites represented yet'
		);

		hackyValueInsertionAndSave( subject, [ 'English (en)', 'London' ], 'enwiki' );

		assert.deepEqual(
			subject.getRepresentedSiteIds(),
			[ 'enwiki' ],
			'after site has been used, the site will be returned'
		);

		hackyValueInsertionAndSave( subject, [ 'dewiki', 'Berlin' ], 'dewiki' );

		assert.deepEqual(
			subject.getRepresentedSiteIds(),
			[ 'enwiki', 'dewiki' ],
			'after another site link has been entered, both sites will be returned'
		);
	} );

	QUnit.test( 'isFull()', function( assert ) {
		var subject = this.subject;

		assert.ok(
			!subject.isFull(),
			'not full initially'
		);

		hackyValueInsertionAndSave( subject, [ 'dewiki', 'Berlin' ], 'dewiki' );

		assert.ok(
			!subject.isFull(),
			'not full after entering one site link'
		);

		hackyValueInsertionAndSave( subject, [ 'enwiki', 'London' ], 'enwiki' );

		assert.ok(
			subject.isFull(),
			'full after entering site links for all available sites'
		);
	} );

	QUnit.test( 'no value rows in table initially', function( assert ) {
		assert.strictEqual(
			this.subject.getValues( true ).length,
			0,
			'no values or pending values in edit tool initially'
		);

		assertValuesInTableSections(
			assert,
			this.subject,
			{ thead: 0, tbody: 0, tfoot: 0 },
			'initially'
		);
	} );

	QUnit.test( 'pending value gets moved from tfoot to tbody after it got saved', function( assert ) {
		var subject = this.subject;

		assertValuesInTableSections(
			assert,
			subject,
			{ tbody: 0, tfoot: 0 },
			'initially'
		);

		hackyValueInsertionAndSave( subject, [ 'dewiki', 'Berlin' ], 'dewiki' );

		assertValuesInTableSections(
			assert,
			subject,
			{ tbody: 1, tfoot: 0 },
			'after entering and saving first value'
		);

		hackyValueInsertionAndSave( subject, [ 'enwiki', 'London' ], 'enwiki' );

		assertValuesInTableSections(
			assert,
			subject,
			{ tbody: 2, tfoot: 0 },
			'after saving second value'
		);
	} );

	QUnit.test( 'pending value gets removed from tfoot after cancelling value insertion', function( assert ) {
		var subject = this.subject;

		assertValuesInTableSections(
			assert,
			subject,
			{ tbody: 0, tfoot: 0 },
			'initially'
		);

		var newValue = hackyValueInsertion( this.subject, [ 'dewiki', 'Berlin' ], 'dewiki' );

		assertValuesInTableSections(
			assert,
			subject,
			{ tbody: 0, tfoot: 1 },
			'after inserting pending value'
		);

		newValue.stopEditing( false );

		assertValuesInTableSections(
			assert,
			subject,
			{ tbody: 0, tfoot: 0 },
			'after cancelling pending value insertion'
		);
	} );

	QUnit.test( 'adding a new editable site link', function( assert ) {

		assert.ok(
			this.subject._editableValues instanceof Array,
			'editable values initiated correctly'
		);

		var rawNewValue = [ 'Deutsch (de)', 'Berlin' ];
		var newValue = hackyValueInsertion( this.subject, rawNewValue, 'dewiki' );

		assert.strictEqual(
			this.subject.getValues().length,
			0,
			'getValues() should return no elements since the new one is still pending'
		);

		assert.strictEqual(
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
				['dewiki', 'Berlin']
			),
			'new value has the value set in enterNewValue( value )'
		);

		assert.strictEqual(
			newValue.startEditing(),
			false,
			'start editing already active, call function again'
		);

		assert.strictEqual(
			newValue.stopEditing( true ).promisor.apiAction,
			newValue.API_ACTION.SAVE,
			'stopped editing (save), true returned because value has changed (it was created)'
		);

		assert.strictEqual(
			this.subject.enterNewValue().siteIdInterface.getResultSetMatch( rawNewValue ),
			null,
			'The site id set already should not be available in the set of suggestions anymore'
		);

		this.subject.destroy();

		assert.strictEqual(
			this.subject._editableValues,
			null,
			'destroyed editable values'
		);

	} );


}( mediaWiki, wikibase, jQuery, QUnit ) );
