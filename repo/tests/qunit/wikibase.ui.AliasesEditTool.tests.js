/**
 * QUnit tests for aliases edit tool
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup Wikibase
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
			this.subject = new wb.ui.AliasesEditTool( initialStructure	);

			ok( // TODO: shouldn't use the global 'ok' and get the 'assert' stuff somehow
				this.subject instanceof wb.ui.AliasesEditTool,
				'instantiated AliasesEditTool'
			);
		},
		teardown: function( assert ) {
			var self = this;
			var values = this.subject.getValues();
			this.subject.destroy();

			// basic check whether initial structure was restored
			var initialStructure = true;
			this.initialStructureMembers.each( function() {
				initialStructure = initialStructure && $( this ).parent().is( self.subject._subject );
			} );

			ok( // TODO: (same as in 'setup')
				initialStructure,
				'DOM nodes from initial aliases edit tool are in the right place again'
			);

			equal( // TODO: (same as in 'setup')
				this.initialStructureMembers.length + values.length,
				this.subject._subject.children().length,
				'No additional DOM nodes left (except those of inserted values)'
			);

			this.subject = null;
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

	QUnit.test( 'Test with creating new EditableAliases', initAliasesTest );

	QUnit.test( 'Test creating and removing EditableAliases from edit tool', function( assert ) {
		initAliasesTest.call( this, assert );

		this.subject.getValues()[0].remove();

		assert.ok(
			this.subject.getValues.length,
			0,
			'Empty after removing EditableAliases instance'
		)
	} );

}( mediaWiki, wikibase, jQuery, QUnit ) );
