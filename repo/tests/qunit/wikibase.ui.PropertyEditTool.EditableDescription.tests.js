/**
 * QUnit tests for editable description component
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author H. Snater <mediawiki@snater.com>
 */

( function( mw, wb, $, QUnit, undefined ) {
	'use strict';

	QUnit.module( 'wikibase.ui.PropertyEditTool.EditableDescription', QUnit.newWbEnvironment( {
		setup: function() {
			var $node = $( '<div><div class="wb-value"/></div>' );
			$( '<div/>', { id: 'parent' } ).append( $node );

			var propertyEditTool = new wb.ui.PropertyEditTool( $node );
			this.subject = wb.ui.PropertyEditTool.EditableDescription.newFromDom( $node );
			var toolbar = propertyEditTool._buildSingleValueToolbar();
			this.subject.setToolbar( toolbar );
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
