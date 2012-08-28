/**
 * QUnit tests for editable site link
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
	module( 'wikibase.ui.PropertyEditTool.EditableSiteLink', window.QUnit.newWbEnvironment( {
		setup: function() {
			this.node = $( '<tr/>', { id: 'subject' } );
			this.node.append( $( '<td/>', { 'class': 'child' } ) );
			this.node.append( $( '<td/>', {
				'class': 'child',
				text: 'en'
			} ) );
			var propertyEditTool = new window.wikibase.ui.PropertyEditTool( this.node );
			this.editableSiteLink = new window.wikibase.ui.PropertyEditTool.EditableSiteLink;
			var toolbar = propertyEditTool._buildSingleValueToolbar( this.editableSiteLink );
			this.editableSiteLink.init( this.node, toolbar );
			this.strings = {
				valid: [ 'test', 'test 2' ],
				invalid: [ '' ]
			};

			ok(
				this.editableSiteLink._subject.html() == this.node.html(),
				'initiated DOM'
			);

		},
		teardown: function() {
			this.editableSiteLink.destroy();

			equal(
				this.editableSiteLink._toolbar,
				null,
				'destroyed toolbar'
			);

			equal(
				this.editableSiteLink._instances,
				null,
				'destroyed instances'
			);

			this.editableSiteLink = null;
			this.strings = null;
		}

	} ) );


	test( 'check init', function() {

		equal(
			this.editableSiteLink._toolbar.editGroup.displayRemoveButton,
			true,
			'show remove button'
		);

		ok(
			this.editableSiteLink.siteIdInterface instanceof window.wikibase.ui.PropertyEditTool.EditableValue.SiteIdInterface,
			'instantiated site id interface'
		);

		ok(
			this.editableSiteLink.pageNameInterface instanceof window.wikibase.ui.PropertyEditTool.EditableValue.SitePageInterface,
			'instantiated site page interface'
		);

		equal(
			this.editableSiteLink._interfaces.length,
			2,
			'has 2 input interfaces'
		);

		ok(
			this.editableSiteLink.getInputHelpMessage() !== '' && typeof this.editableSiteLink.getInputHelpMessage() != 'undefined',
			'has input help message'
		);


	} );


}() );
