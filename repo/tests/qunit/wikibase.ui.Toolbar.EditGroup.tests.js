/**
 * QUnit tests for toolbar edit group
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
	module( 'wikibase.ui.Toolbar.EditGroup', window.QUnit.newWbEnvironment( {
		setup: function() {
			var node = $( '<div/>', { id: 'subject' } );
			$( '<div/>', { id: 'parent' } ).append( node );
			var propertyEditTool = new window.wikibase.ui.PropertyEditTool( node );
			this.editableValue = new window.wikibase.ui.PropertyEditTool.EditableValue;
			var toolbar = propertyEditTool._buildSingleValueToolbar( this.editableValue );
			this.editGroup = toolbar.editGroup;

			ok(
				this.editableValue == this.editGroup._editableValue,
				'initiated: set editable value'
			);

		},
		teardown: function() {
			this.editGroup.destroy();

			equal(
				this.editGroup.innerGroup,
				null,
				'destroyed inner group'
			);

			equal(
				this.editGroup.tooltipAnchor,
				null,
				'destroyed tooltip'
			);

			equal(
				this.editGroup.btnEdit,
				null,
				'destroyed edit button'
			);

			equal(
				this.editGroup.btnCancel,
				null,
				'destroyed cancel button'
			);

			equal(
				this.editGroup.btnSave,
				null,
				'destroyed save button'
			);

			equal(
				this.editGroup.btnRemove,
				null,
				'destroyed remove button'
			);

			this.editGroup = null;
			this.editableValue = null;

		}
	} ) );


	test( 'init check', function() {

		ok(
			this.editGroup.innerGroup instanceof window.wikibase.ui.Toolbar.Group,
			'initiated inner edit group'
		);

		ok(
			this.editGroup.tooltipAnchor instanceof window.wikibase.ui.Toolbar.Label,
			'initiated tooltip'
		);

		equal(
			this.editGroup.tooltipAnchor.stateChangeable,
			false,
			'tooltip state is not changeable'
		);

		ok(
			this.editGroup.btnEdit instanceof window.wikibase.ui.Toolbar.Button,
			'initiated edit button'
		);

		ok(
			this.editGroup.btnCancel instanceof window.wikibase.ui.Toolbar.Button,
			'initiated cancel button'
		);

		ok(
			this.editGroup.btnSave instanceof window.wikibase.ui.Toolbar.Button,
			'initiated save button'
		);

		ok(
			this.editGroup.btnRemove instanceof window.wikibase.ui.Toolbar.Button,
			'initiated remove button'
		);

		equal(
			this.editGroup.displayRemoveButton,
			true,
			'remove button will not be displayed'
		);

		equal(
			this.editGroup.renderItemSeparators,
			false,
			'item separators will not be displayed'
		);

		equal(
			this.editGroup.innerGroup.hasElement( this.editGroup.btnEdit ),
			true,
			'edit button is in inner group'
		);


	} );

}() );
