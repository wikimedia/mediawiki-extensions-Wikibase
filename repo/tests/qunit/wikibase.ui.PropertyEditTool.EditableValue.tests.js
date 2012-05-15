/**
 * QUnit tests for editable value component of property edit tool
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file wikibase.ui.PropertyEditTool.EditableValue.tests.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author H. Snater
 * @author Daniel Werner
 */
'use strict';


( function () {
	module( 'wikibase.ui.PropertyEditTool.EditableValue', window.QUnit.newWbEnvironment( null, null, {
		setup: function() {
			var node = $( '<div/>', { id: 'subject' } );
			$( '<div/>', { id: 'parent' } ).append( node );
			var propertyEditTool = new window.wikibase.ui.PropertyEditTool( node );
			this.editableValue = new window.wikibase.ui.PropertyEditTool.EditableValue;
			this.editableValue.queryApi = function( deferred, apiAction ) { // override AJAX API call
				deferred.resolve( '' );
			};
			var toolbar = propertyEditTool._buildSingleValueToolbar( this.editableValue );
			this.editableValue._init( node, toolbar );
			this.strings = {
				valid: [ 'test', 'test 2' ],
				invalid: [ '' ]
			};
			this.errors = [ // simulated error objects being returned from the API
				{ 'error':
					{
						'code': 'no-permissions',
						'info': 'The logged in user does not have sufficient rights'
					}
				}
			];

			equal(
				this.editableValue._getToolbarParent().attr( 'id' ),
				'parent',
				'parent node for toolbar exists'
			);

			ok(
				this.editableValue._interfaces.length == 1
					&& this.editableValue._interfaces[0] instanceof window.wikibase.ui.PropertyEditTool.EditableValue.Interface,
				'initialized one interface'
			);

		},
		teardown: function() {
			this.editableValue.destroy();

			equal(
				this.editableValue._toolbar,
				null,
				'destroyed toolbar'
			);

			equal(
				this.editableValue._instances,
				null,
				'destroyed instances'
			);

			this.editableValue = null;
			this.strings = null;
		}

	} ) );


	test( 'initial check', function() {

		equal(
			this.editableValue.getInputHelpMessage(),
			'',
			'checked help message'
		);

		equal(
			this.editableValue.isPending(),
			false,
			'value is not pending'
		);

		equal(
			this.editableValue.isInEditMode(),
			false,
			'not in edit mode'
		);

	} );


	test( 'edit', function() {

		equal(
			this.editableValue.startEditing(),
			true,
			'started edit mode'
		);

		equal(
			this.editableValue.isInEditMode(),
			true,
			'is in edit mode'
		);

		this.editableValue.setValue( this.strings['valid'][0] );

		ok(
			this.editableValue.getValue() instanceof Array && this.editableValue.getValue()[0] == this.strings['valid'][0],
			'changed value'
		);

		equal(
			this.editableValue.stopEditing( false ),
			false,
			"stopped edit mode, don't save value"
		);

		ok(
			this.editableValue.getValue()[0] != this.strings['valid'][0],
			'value not saved after leaving edit mode without saving value'
		);

		equal(
			this.editableValue.stopEditing(),
			false,
			'stop edit mode again'
		);

		equal(
			this.editableValue.startEditing(),
			true,
			'started edit mode'
		);

		this.editableValue.setValue( this.strings['valid'][0] );

		ok(
			this.editableValue.getValue() instanceof Array && this.editableValue.getValue()[0] == this.strings['valid'][0],
			'changed value'
		);

		equal(
			this.editableValue.stopEditing( true ),
			true,
			'stopped edit mode, save'
		);

		equal(
			this.editableValue.isInEditMode(),
			false,
			'is not in edit mode'
		);

		this.editableValue.setValue( this.strings['valid'][1] );

		ok(
			this.editableValue.getValue() instanceof Array && this.editableValue.getValue()[0] == this.strings['valid'][1],
			'changed value'
		);

		equal(
			this.editableValue.startEditing(),
			true,
			'started edit mode'
		);

		equal(
			this.editableValue.startEditing(),
			false,
			'try to start edit mode again'
		);

		equal(
			this.editableValue.validate( [this.strings['invalid'][0]] ),
			false,
			'empty value not validated'
		);

		equal(
			this.editableValue.validate( [this.strings['valid'][0]] ),
			true,
			'validated input'
		);

		this.editableValue.setValue( this.strings['invalid'][0] );

		ok(
			this.editableValue.getValue() instanceof Array && this.editableValue.getValue()[0] == this.strings['invalid'][0],
			'set empty value'
		);

		equal(
			this.editableValue.isEmpty(),
			true,
			'editable value is empty'
		);

		ok(
			this.editableValue.getValue() instanceof Array && this.editableValue.getInitialValue()[0] == this.strings['valid'][1],
			'checked initial value'
		);

		equal(
			this.editableValue.valueCompare( this.editableValue.getValue(), this.editableValue.getInitialValue() ),
			false,
			'compared current and initial value'
		);

		this.editableValue.setValue( this.strings['valid'][1] );

		ok(
			this.editableValue.getValue() == this.strings['valid'][1],
			'reset value to initial value'
		);

		equal(
			this.editableValue.valueCompare( this.editableValue.getValue(), this.editableValue.getInitialValue() ),
			true,
			'compared current and initial value'
		);

		this.editableValue.remove();

	} );

	test( 'error handling', function() {

		this.editableValue.queryApi = $.proxy( function( deferred, apiAction ) {
			deferred.reject( 'error', this.errors[0] );
		}, this );

		this.editableValue.startEditing();
		this.editableValue.setValue( this.strings['valid'][0] );

		equal(
			this.editableValue.isInEditMode(),
			true,
			'started editing ans set value'
		);

		this.editableValue.stopEditing( true );

		equal(
			this.editableValue.isInEditMode(),
			true,
			'is still in edit mode after receiving error'
		);

		ok(
			this.editableValue._toolbar.editGroup.btnSave.tooltip instanceof window.wikibase.ui.Tooltip,
			'attached tooltip to save button'
		);

		this.editableValue.stopEditing();

		equal(
			this.editableValue.isInEditMode(),
		false,
			'cancelled editing'
		);

		this.editableValue.remove( true );

		equal(
			this.editableValue.getValue()[0],
			this.editableValue.getInitialValue()[0],
			'emptied input interface resetting to default value and preserving the input interface'
		);

		this.editableValue.remove( false );

		ok(
			this.editableValue._toolbar.editGroup.btnRemove.tooltip instanceof window.wikibase.ui.Tooltip,
			'attached tooltip to remove button after trying to remove with API action'
		);

	} );


}() );
