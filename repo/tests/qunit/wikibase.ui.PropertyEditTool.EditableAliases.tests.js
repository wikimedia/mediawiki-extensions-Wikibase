/**
 * QUnit tests for PropertyEditTool.EditableAliases component
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
	 * Factory for creating a new EditableAliases object suited for testing.
	 *
	 * @return wb.ui.PropertyEditTool.EditableAliases
	 */
	var newTestEditableAliases = function() {
		var $node = $( '<ul/>', { id: 'parent' } ).appendTo( 'body' );
		var propertyEditTool = new wb.ui.PropertyEditTool( $node );
		var subject = wb.ui.PropertyEditTool.EditableAliases.newFromDom( $node );
		var toolbar = propertyEditTool._buildSingleValueToolbar();
		subject.setToolbar( toolbar );
		return subject;
	};

	QUnit.module( 'wikibase.ui.PropertyEditTool.EditableAliases', QUnit.newWbEnvironment( {
		setup: function() {
			this.values = [ 'a', 'b', 'c', 'd' ];
			this.string = 'somestring';
		},
		teardown: function() {}
	} ) );

	QUnit.test( 'basic test', function( assert ) {

		var subject = newTestEditableAliases();

		assert.ok(
			subject._interfaces.length === 1
				&& subject._interfaces[0] instanceof wb.ui.PropertyEditTool.EditableValue.AliasesInterface,
			'initialized one interface'
		);

		assert.equal(
			subject.getValue()[0].length,
			0,
			'no value set'
		);

		assert.equal(
			subject.setValue( this.values )[0].length,
			this.values.length,
			'set values'
		);

		assert.equal(
			subject.setValue( this.string )[0].length,
			this.values.length,
			'tried to set invalid value'
		);

		assert.equal(
			subject.setValue( [] )[0].length,
			0,
			'set empty value'
		);

		subject.destroy();

		assert.equal(
			subject._toolbar,
			null,
			'destroyed toolbar'
		);

		assert.equal(
			subject._interfaces,
			null,
			'destroyed interfaces'
		);

	} );

}( wikibase, jQuery, QUnit ) );
