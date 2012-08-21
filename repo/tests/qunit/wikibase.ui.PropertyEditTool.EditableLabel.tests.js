/**
 * QUnit tests for editable label component
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


( function () {
	module( 'wikibase.ui.PropertyEditTool.EditableLabel', window.QUnit.newWbEnvironment( {
		setup: function() {
			var node = $( '<div/>', { id: 'subject' } );
			$( '<div/>', { id: 'parent' } ).append( node );
			var propertyEditTool = new window.wikibase.ui.PropertyEditTool( node );
			this.subject = new window.wikibase.ui.PropertyEditTool.EditableLabel;
			var toolbar = propertyEditTool._buildSingleValueToolbar( this.subject );
			this.subject._init( node, toolbar );

			ok(
				this.subject instanceof window.wikibase.ui.PropertyEditTool.EditableLabel,
				'instantiated editable label'
			);

		},
		teardown: function() {
			this.subject.destroy();

			equal(
				this.subject._toolbar,
				null,
				'destroyed toolbar'
			);

			equal(
				this.subject._instances,
				null,
				'destroyed instances'
			);

			this.subject = null;
		}

	} ) );


	test( 'basic', function() {

		equal(
			this.subject._interfaces.length,
			1,
			'initialized single interface'
		);

		equal(
			typeof this.subject.getApiCallParams(),
			'object',
			'getApiParams returns an object'
		);

		ok(
			this.subject.getInputHelpMessage() != '',
			'help message not empty'
		);

	} );


}() );
