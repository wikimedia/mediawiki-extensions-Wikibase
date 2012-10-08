/**
 * QUnit tests for editable description component
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author H. Snater
 */

( function( mw, wb, $, QUnit, undefined ) {
	'use strict';

	QUnit.module( 'wikibase.ui.PropertyEditTool.EditableDescription', QUnit.newWbEnvironment( {
		setup: function() {
			var node = $( '<div><div class="wb-value"/></div>' );
			$( '<div/>', { id: 'parent' } ).append( node );
			var propertyEditTool = new wb.ui.PropertyEditTool( node );
			this.subject = new wb.ui.PropertyEditTool.EditableDescription;
			var toolbar = propertyEditTool._buildSingleValueToolbar( this.subject );
			this.subject.init( node, toolbar );
		},
		teardown: function() { }

	} ) );


	QUnit.test( 'basic', function( assert ) {

		assert.ok(
			this.subject instanceof wb.ui.PropertyEditTool.EditableDescription,
			'instantiated editable description'
		);

		assert.equal(
			this.subject._interfaces.length,
			1,
			'initialized single interface'
		);

		assert.equal(
			typeof this.subject.getApiCallParams(),
			'object',
			'getApiParams returns an object'
		);

		assert.ok(
			this.subject.getInputHelpMessage() !== '',
			'help message not empty'
		);

		this.subject.destroy();

		assert.equal(
			this.subject._toolbar,
			null,
			'destroyed toolbar'
		);

		assert.equal(
			this.subject._instances,
			null,
			'destroyed instances'
		);

	} );


}( mediaWiki, wikibase, jQuery, QUnit ) );
