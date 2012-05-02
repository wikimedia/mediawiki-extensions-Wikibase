/**
 * QUnit tests for Label prototype for toolbars
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file wikibase.ui.Toolbar.Label.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
'use strict';

( function () {
	module( 'wikibase.ui.Toolbar.Label', {
		setup: function() {

			this.label = new window.wikibase.ui.Toolbar.Label( 'Text' );

			ok(
				( typeof this.label._elem == 'object' ) && this.label.getContent() == 'Text',
				'Label was initialized properly'
			);

		},
		teardown: function() {
			this.label.destroy();

			equal(
				this.label._elem,
				null,
				'destroyed button'
			);
		}

	} );

	test( 'set and get content', function() {

		this.label.setContent( 'Foo' );

		equal(
			this.label.getContent(),
			'Foo',
			'Content equals the content set before'
		);

		var jQueryObj = $( '<span/>' );
		this.label.setContent( jQueryObj );

		equal(
			this.label.getContent()[0],
			jQueryObj[0], // compare with containing node
			'Content equals the content set before'
		);

	} );

}() );
