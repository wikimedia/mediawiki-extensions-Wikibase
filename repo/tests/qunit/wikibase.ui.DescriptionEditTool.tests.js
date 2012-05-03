/**
 * QUnit tests description edit tool
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file wikibase.ui.DescriptionEditTool.tests.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author H. Snater
 */
'use strict';


( function() {
	module( 'wikibase.ui.DescriptionEditTool', {
		setup: function() {
			this.node = $( '<div/>', { text: 'Text' } );
			this.subject = new window.wikibase.ui.DescriptionEditTool( this.node );

			ok(
				this.subject instanceof window.wikibase.ui.DescriptionEditTool,
				'instantiated DescriptionEditTool'
			);

		},
		teardown: function() {
			this.subject.destroy();

			equal(
				this.node.children().length,
				0,
				'cleaned DOM'
			);

			this.subject = null;
			this.span = null;
			this.h1 = null;
		}

	} );


	test( 'basic check', function() {

		equal(
			this.subject.getEditableValuePrototype(),
			window.wikibase.ui.PropertyEditTool.EditableDescription,
			'retrieved prototype'
		);

		equal(
			this.subject.allowsMultipleValues,
			false,
			'does not allow multiple values'
		);

	} );


}() );
