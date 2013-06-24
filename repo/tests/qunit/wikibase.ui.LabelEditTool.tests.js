/**
 * QUnit tests heading edit tool
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

	QUnit.module( 'wikibase.ui.LabelEditTool', QUnit.newWbEnvironment( {
		setup: function() {
			this.h1 = $( '<h1/>', { 'class': 'wb-firstHeading' } );
			this.span = $( '<span/>' ).append( $( '<span/>', {
				'class': 'wb-value',
				text: 'Text'
			} ) ).appendTo( this.h1 );
			this.subject = new wb.ui.LabelEditTool( this.h1 );
		},
		teardown: function() {}
	} ) );

	QUnit.test( 'basic check', function( assert ) {

		assert.ok(
			this.subject instanceof wb.ui.LabelEditTool,
			'instantiated HeadingEditTool'
		);

		assert.equal(
			this.subject._getValueElems()[0],
			this.span[0],
			'checked getting value element'
		);

		assert.equal(
			this.subject.getPropertyName(),
			'label',
			'property name is label'
		);

		assert.equal(
			this.subject.getEditableValuePrototype(),
			wb.ui.PropertyEditTool.EditableLabel,
			'retrieved prototype'
		);

		assert.equal(
			this.subject.getOption( 'allowsMultipleValues' ),
			false,
			'does not allow multiple values'
		);

		this.subject.destroy();

		assert.equal(
			this.h1.children().length + this.h1.children().first().children().length,
			1,
			'cleaned DOM'
		);

		assert.equal(
			this.h1.children()[0],
			this.span[0],
			'span child remains'
		);

	} );

}( mediaWiki, wikibase, jQuery, QUnit ) );
