/**
 * QUnit tests for aliases edit tool
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function( mw, wb, $, QUnit ) {
	'use strict';
	QUnit.module( 'wikibase.ui.AliasesEditTool', QUnit.newWbEnvironment( {
		setup: function() {
			/**
			 * Holds the original dom structure the AliasesEditTool was initialized with
			 * @var jQuery
			 */
			var initialStructure = wb.ui.AliasesEditTool.getEmptyStructure();
			this.initialStructureMembers = initialStructure.children();
			this.subject = new wb.ui.AliasesEditTool( initialStructure );

			QUnit.assert.ok(
				this.subject instanceof wb.ui.AliasesEditTool,
				'instantiated AliasesEditTool'
			);
		},
		teardown: function() {
			var self = this;
			this.subject.destroy();

			// basic check whether initial structure was restored
			var hasInitialStructure = true;
			this.initialStructureMembers.each( function() {
				hasInitialStructure = hasInitialStructure && $( this ).parent().is( self.subject.getSubject() );
			} );

			QUnit.assert.ok(
				hasInitialStructure,
				'DOM nodes from initial aliases edit tool are in the right place again'
			);

			QUnit.assert.equal(
				this.initialStructureMembers.length + this.subject.getValues().length,
				self.subject.getSubject().children().length,
				'No additional DOM nodes left (except those of inserted values)'
			);

			this.subject = null;
			this.initialStructureMembers = null;
		}

	} ) );

	// base for following tests, creates some values
	var initAliasesTest = function( assert ) {
		var newVal = this.subject.enterNewValue( [ 'alias 1', 'two', 'three' ] );
		assert.ok(
			newVal instanceof wb.ui.PropertyEditTool.EditableAliases,
			'Value entered has instance of EditableAlias'
		);
	};

	// This is the same as the next test, but the next one does additional stuff, so the destroy() in teardown
	// might fail independently, depending on the edit tools state.
	QUnit.test( 'Test with creating new EditableAliases', initAliasesTest );

	QUnit.test( 'Test creating and removing EditableAliases from edit tool', function( assert ) {
		initAliasesTest.call( this, assert );

		var aliasesValue = this.subject.getValues()[0];

		aliasesValue.triggerApi = function( deferred, apiAction ) { // override AJAX API call
			// dummy response
			deferred.resolve( {
				entity: {
					id: 'someid',
					type: 'item',
					lastrevid: 1234
				},
				success: 1
			} ).promise();
		};
		aliasesValue.remove();

		assert.equal(
			this.subject.getValues().length,
			0,
			'Empty after removing EditableAliases instance'
		);
	} );

}( mediaWiki, wikibase, jQuery, QUnit ) );
