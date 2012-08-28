/**
 * QUnit tests for editable aliases component of property edit tool
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
	module( 'wikibase.ui.PropertyEditTool.EditableAliases', window.QUnit.newWbEnvironment( {
		setup: function() {
			var node = $( '<ul/>', { id: 'parent' } ).appendTo( 'body' );
			this.propertyEditTool = new window.wikibase.ui.PropertyEditTool( node );
			this.subject = new window.wikibase.ui.PropertyEditTool.EditableAliases();

			var toolbar = this.propertyEditTool._buildSingleValueToolbar( this.subject );
			this.subject.init( node, toolbar );
			this.values = [ 'a', 'b', 'c', 'd' ];
			this.string = 'somestring';

			ok(
				this.subject._interfaces.length == 1
					&& this.subject._interfaces[0] instanceof window.wikibase.ui.PropertyEditTool.EditableValue.AliasesInterface,
				'initialized one interface'
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
				this.subject._interfaces,
				null,
				'destroyed interfaces'
			);

			this.propertyEditTool.destroy();
			this.propertyEditTool = null;
			this.subject = null;
			this.strings = null;
		}

	} ) );


	test( 'basic test', function() {

		equal(
			this.subject.getValue()[0].length,
			0,
			'no value set'
		);

		equal(
			this.subject.setValue( this.values )[0].length,
			this.values.length,
			'set values'
		);

		equal(
			this.subject.setValue( this.string )[0].length,
			this.values.length,
			'tried to set invalid value'
		);

		equal(
			this.subject.setValue( [] )[0].length,
			0,
			'set empty value'
		);

	} );

}() );
