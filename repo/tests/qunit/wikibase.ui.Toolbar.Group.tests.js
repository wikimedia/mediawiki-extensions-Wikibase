/**
 * QUnit tests for edit group
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author H. Snater
 */
'use strict';


( function() {
	module( 'wikibase.ui.Toolbar.Group', window.QUnit.newWbEnvironment( {
		setup: function() {
			this.subject = new window.wikibase.ui.Toolbar.Group();

			ok(
				this.subject instanceof window.wikibase.ui.Toolbar.Group,
				'instantiated group'
			);

		},
		teardown: function() {
			this.subject.destroy();

			equal(
				this.subject._elem,
				null
			);

			this.subject = null;
		}
	} ) );


	test( 'basic', function() {

		equal(
			this.subject._elem.length,
			1,
			'created DOM structure'
		);

		equal(
			this.subject._elem[0].nodeName,
			'DIV',
			'child element is a DIV'
		);

		equal(
			this.subject.renderItemSeparators,
			true,
			'render item separators'
		);

	} );


}() );
