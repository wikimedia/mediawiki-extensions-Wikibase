/**
 * QUnit tests description edit tool
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

	QUnit.module( 'wikibase.ui.DescriptionEditTool', QUnit.newWbEnvironment( {
		setup: function() {
			this.parentNode = $( '<div/>' );
			this.text = 'Text';
			this.node = $( '<div/>' ).append( $( '<div/>', {
				text: this.text,
				'class': 'wb-property-container-value'
			} ) );
			this.parentNode.append( this.node );
			this.subject = new wb.ui.DescriptionEditTool( this.parentNode );
		},
		teardown: function() {}
	} ) );

	QUnit.test( 'basic check', function( assert ) {

		assert.ok(
			this.subject instanceof wb.ui.DescriptionEditTool,
			'instantiated DescriptionEditTool'
		);

		assert.equal(
			this.subject.getEditableValuePrototype(),
			wb.ui.PropertyEditTool.EditableDescription,
			'retrieved prototype'
		);

		assert.equal(
			this.subject.getOption( 'allowsMultipleValues' ),
			false,
			'does not allow multiple values'
		);

		this.subject.destroy();

		assert.equal(
			this.node.children().length + this.node.children().first().children().length,
			1,
			'cleaned DOM'
		);

		assert.equal(
			this.node.text(),
			this.text,
			'plain text remains'
		);

	} );

}( mediaWiki, wikibase, jQuery, QUnit ) );
