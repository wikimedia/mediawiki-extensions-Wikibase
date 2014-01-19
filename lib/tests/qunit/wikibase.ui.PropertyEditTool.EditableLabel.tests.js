/**
 * QUnit tests for editable label component
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author H. Snater <mediawiki@snater.com>
 * @author Marius Hoch < hoo@online.de >
 */

( function( mw, wb, $, QUnit, undefined ) {
	'use strict';

	function setup( options ) {
		options = options || {};

		var $node = $( '<div><div class="wb-value"/></div>' );
		$( '<div/>', { id: 'parent' } ).append( $node );

		var propertyEditTool = new wb.ui.PropertyEditTool( $node ),
			subject = wb.ui.PropertyEditTool.EditableLabel.newFromDom( $node, options ),
			toolbar = propertyEditTool._buildSingleValueToolbar();

		subject.setToolbar( toolbar );

		return subject;
	}

	QUnit.module( 'wikibase.ui.PropertyEditTool.EditableLabel', QUnit.newWbEnvironment( {
		setup: function() {
			this.subject = setup();
		},
		teardown: function() {}
	} ) );

	QUnit.test( 'basic', function( assert ) {

		assert.ok(
			this.subject instanceof wb.ui.PropertyEditTool.EditableLabel,
			'instantiated editable label'
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

	QUnit.test( 'placeholder', function( assert ) {
		var oldGetLanguageNameByCode = wb.getLanguageNameByCode;

		wb.getLanguageNameByCode = function( code ) {
			if ( code === 'de' ) {
				return 'Deutsch';
			} else {
				return '';
			}
		}

		var withLanguage = setup( { valueLanguageContext: 'de' } ),
			withoutLanguage = setup( { valueLanguageContext: 'ru' } );

		assert.equal(
			withLanguage._interfaces[0]._options.inputPlaceholder,
			mw.msg(
				'wikibase-label-edit-placeholder-language-aware',
				'Deutsch'
			)
		);

		assert.equal(
			withoutLanguage._interfaces[0]._options.inputPlaceholder,
			mw.msg( 'wikibase-label-edit-placeholder' )
		);

		wb.getLanguageNameByCode = oldGetLanguageNameByCode;
	} );

}( mediaWiki, wikibase, jQuery, QUnit ) );
