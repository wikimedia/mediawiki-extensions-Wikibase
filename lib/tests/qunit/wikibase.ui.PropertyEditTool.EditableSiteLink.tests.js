/**
 * QUnit tests for EditableSiteLink
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
	 * Factory for creating a new EditableSiteLink suited for testing.
	 *
	 * @param {jQuery} [$node]
	 * @return wb.ui.PropertyEditTool.EditableSiteLink
	 */
	var newTestEditableSiteLink = function( $node ) {
		if ( $node === undefined ) {
			$node = $( '<tr/>' ).attr( 'id', 'subject' );
		}
		$node.append( $( '<td/>' ).addClass( 'child' ) );
		$node.append( $( '<td/>' ).addClass( 'child' ).text( 'en' ) );
		var propertyEditTool = new wb.ui.PropertyEditTool( $node );
		var editableSiteLink = wb.ui.PropertyEditTool.EditableSiteLink.newFromDom( $node );
		var toolbar = propertyEditTool._buildSingleValueToolbar();
		editableSiteLink.setToolbar( toolbar );
		return editableSiteLink;
	};

	QUnit.module( 'wikibase.ui.PropertyEditTool.EditableSiteLink', QUnit.newWbEnvironment( {
		setup: function() {
			this.strings = {
				valid: ['test', 'test 2'],
				invalid: ['']
			};
		},
		teardown: function() {}
	} ) );

	QUnit.test( 'check init', function( assert ) {

		var $node = $( '<tr/>' ).attr( 'id', 'subject' );
		var subject = newTestEditableSiteLink( $node );

		assert.ok(
			subject._subject.html() === $node.html(),
			'initiated DOM'
		);

		assert.equal(
			subject._toolbar.$editGroup.data( 'toolbareditgroup' ).option( 'displayRemoveButton' ),
			true,
			'show remove button'
		);

		assert.ok(
			subject.siteIdInterface instanceof wb.ui.PropertyEditTool.EditableValue.SiteIdInterface,
			'instantiated site id interface'
		);

		assert.ok(
			subject.sitePageInterface instanceof wb.ui.PropertyEditTool.EditableValue.SitePageInterface,
			'instantiated site page interface'
		);

		assert.equal(
			subject._interfaces.length,
			2,
			'has 2 input interfaces'
		);

		assert.ok(
			subject.getInputHelpMessage() !== '' && subject.getInputHelpMessage() !== undefined,
			'has input help message'
		);

		subject.destroy();

		assert.equal(
			subject._toolbar,
			null,
			'destroyed toolbar'
		);

		assert.equal(
			subject._instances,
			null,
			'destroyed instances'
		);

	} );

}( wikibase, jQuery, QUnit ) );
