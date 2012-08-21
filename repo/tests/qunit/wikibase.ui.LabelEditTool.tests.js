/**
 * QUnit tests heading edit tool
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
	module( 'wikibase.ui.LabelEditTool', window.QUnit.newWbEnvironment( {
		setup: function() {
			this.h1 = $( '<h1/>', { 'class': 'wb-firstHeading' } );
			this.span = $( '<span/>', { text: 'Text' } ).appendTo( this.h1 );
			this.subject = new window.wikibase.ui.LabelEditTool( this.h1 );

			ok(
				this.subject instanceof window.wikibase.ui.LabelEditTool,
				'instantiated HeadingEditTool'
			);

		},
		teardown: function() {
			this.subject.destroy();

			equal(
				this.h1.children().length,
				1,
				'cleaned DOM'
			);

			equal(
				this.h1.children()[0],
				this.span[0],
				'span child remains'
			);

			this.subject = null;
			this.span = null;
			this.h1 = null;
		}

	} ) );


	test( 'basic check', function() {

		equal(
			this.subject._getValueElems()[0],
			this.span[0],
			'checked getting value element'
		);

		equal(
			this.subject.getPropertyName(),
			'label',
			'property name is label'
		);

		equal(
			this.subject.getEditableValuePrototype(),
			window.wikibase.ui.PropertyEditTool.EditableLabel,
			'retrieved prototype'
		);

		equal(
			this.subject.allowsMultipleValues,
			false,
			'does not allow multiple values'
		);

	} );


}() );
