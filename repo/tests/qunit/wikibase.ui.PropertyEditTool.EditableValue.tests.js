/**
 * QUnit tests for editable value component of property edit tool
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author H. Snater
 * @author Daniel Werner
 */

( function( mw, wb, $, QUnit, undefined ) {
	'use strict';

	QUnit.module( 'wikibase.ui.PropertyEditTool.EditableValue', QUnit.newWbEnvironment( {
		setup: function() {
			var node = $( '<div/>', { id: 'parent' } );
			this.propertyEditTool = new wb.ui.PropertyEditTool( node );
			this.editableValue = new wb.ui.PropertyEditTool.EditableValue();

			var toolbar = this.propertyEditTool._buildSingleValueToolbar( this.editableValue );
			this.editableValue.init( node, toolbar );
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
				},
				{ 'error':
					{
						'code': 'some-nonexistant-error-code',
						'info': 'This error code should not be defined.'
					}
				},
				{ 'error':
					{
						'code': 'client-error',
						'info': 'The connection to the client page failed.'
					}
				}
			];

			QUnit.assert.equal(
				this.editableValue._getToolbarParent().parent().attr( 'id' ),
				'parent',
				'parent node for toolbar exists'
			);

			QUnit.assert.equal(
				this.editableValue.getSubject()[0],
				node[0],
				'verified subject node'
			);

			QUnit.assert.ok(
				this.editableValue._interfaces.length === 1
					&& this.editableValue._interfaces[0] instanceof wb.ui.PropertyEditTool.EditableValue.Interface,
				'initialized one interface'
			);


			var self = this;

			this.editableValue.simulateApiFailure = function( error ) {
				if ( error === undefined ) {
					error = 0;
				}
				self.editableValue.queryApi = function( deferred, apiAction ) {
					deferred.reject( 'error', self.errors[ error ] ).promise();
				};
			};

			// have to change value because if value isNew and empty, there will be no API call
			this.editableValue.setValue( this.strings.valid[0] );
			this.editableValue.simulateApiFailure();

			QUnit.assert.ok(
				this.editableValue.remove().state() === 'rejected',
				'simulateApiFailure() we use for testing failures in the API works'
			);

			this.editableValue.setValue( '' ); // reset to initial state

			this.editableValue.simulateApiSuccess = function() {
				self.editableValue.queryApi = function( deferred, apiAction ) { // override AJAX API call
					deferred.resolve( {} ).promise();
				};
			};

			this.editableValue.simulateApiSuccess(); // initial state by default api actions in our tests are a success!

			QUnit.assert.ok(
				this.editableValue.save().state() === 'resolved',
				'simulateApiSuccess() we use for testing success in the API works'
			);
		},
		teardown: function() {
			this.editableValue.destroy();

			QUnit.assert.equal(
				this.editableValue._toolbar,
				null,
				'destroyed toolbar'
			);

			QUnit.assert.equal(
				this.editableValue._instances,
				null,
				'destroyed instances'
			);

			this.propertyEditTool.destroy();
			this.propertyEditTool = null;
			this.editableValue = null;
			this.strings = null;
		}

	} ) );


	QUnit.test( 'initial check', function( assert ) {
		assert.equal(
			this.editableValue.getInputHelpMessage(),
			'',
			'checked help message'
		);

		assert.equal(
			this.editableValue.isPending(), // see todo in EditableValue. This behaves strange, use isNew()
			false,
			'value is not pending'
		);

		assert.equal(
			this.editableValue.isNew(),
			true,
			'value is new'
		);

		assert.equal(
			this.editableValue.isEmpty(),
			true,
			'value is empty'
		);

		assert.equal(
			this.editableValue.valueCompare( this.editableValue.getValue(), '' ),
			true,
			'value is empty string'
		);

		assert.equal(
			this.editableValue.isInEditMode(),
			true,
			'in edit mode initially because empty string is initial value'
		);

		assert.ok(
			this.editableValue.getToolbar() instanceof wikibase.ui.Toolbar,
			'instantiated toolbar'
		);

	} );


	QUnit.test( 'dis- and enabling', function( assert ) {
		assert.equal(
			this.editableValue.enable(),
			true,
			'enabling'
		);
		
		assert.equal(
			this.editableValue.isEnabled(),
			true,
			'is enabled'
		);

		assert.equal(
			this.editableValue.isDisabled(),
			false,
			'not disabled'
		);

		assert.equal(
			this.editableValue.disable(),
			true,
			'disabling'
		);

		assert.equal(
			this.editableValue.isDisabled(),
			true,
			'disabled'
		);

		assert.equal(
			this.editableValue.isEnabled(),
			false,
			'not enabled'
		);

		assert.equal(
			this.editableValue.getToolbar().enable(),
			true,
			'enabling toolbar'
		);

		assert.equal(
			this.editableValue.getState(),
			wb.ui.StateExtension.prototype.STATE.MIXED,
			'mixed state'
		);

		assert.equal(
			this.editableValue.isDisabled(),
			false,
			'not disabled'
		);

		assert.equal(
			this.editableValue.isEnabled(),
			false,
			'not enabled'
		);

		assert.equal(
			this.editableValue.enable(),
			true,
			'enabling'
		);

		assert.equal(
			this.editableValue.isEnabled(),
			true,
			'is enabled'
		);

		assert.equal(
			this.editableValue.isDisabled(),
			false,
			'is not disabled'
		);

	} );


	QUnit.test( 'edit', function( assert ) {
		assert.equal(
			this.editableValue.startEditing(),
			true,
			'started edit mode'
		);

		assert.equal(
			this.editableValue.isInEditMode(),
			true,
			'is in edit mode'
		);

		this.editableValue.setValue( this.strings.valid[0] );

		assert.ok(
			this.editableValue.getValue() instanceof Array && this.editableValue.getValue()[0] == this.strings.valid[0],
			'changed value'
		);

		assert.equal(
			this.editableValue.stopEditing( false ).promisor.apiAction,
			wikibase.ui.PropertyEditTool.EditableValue.prototype.API_ACTION.NONE,
			"stopped edit mode, don't save value"
		);

		assert.ok(
			this.editableValue.getValue()[0] != this.strings.valid[0],
			'value not saved after leaving edit mode without saving value'
		);

		var deferred = this.editableValue.stopEditing( false );

		assert.equal(
			deferred.promisor.apiAction,
			wikibase.ui.PropertyEditTool.EditableValue.prototype.API_ACTION.NONE,
			'stop edit mode again'
		);

		assert.equal(
			this.editableValue.startEditing(),
			true,
			'started edit mode'
		);

		this.editableValue.setValue( this.strings.valid[0] );

		assert.ok(
			this.editableValue.getValue() instanceof Array && this.editableValue.getValue()[0] == this.strings.valid[0],
			'changed value'
		);

		assert.equal(
			this.editableValue.stopEditing( true ).promisor.apiAction,
			wikibase.ui.PropertyEditTool.EditableValue.prototype.API_ACTION.SAVE,
			'stopped edit mode, save'
		);

		assert.equal(
			this.editableValue.isInEditMode(),
			false,
			'is not in edit mode'
		);

		this.editableValue.setValue( this.strings.valid[1] );

		assert.ok(
			this.editableValue.getValue() instanceof Array && this.editableValue.getValue()[0] == this.strings.valid[1],
			'changed value'
		);

		assert.equal(
			this.editableValue.startEditing(),
			true,
			'started edit mode'
		);

		assert.equal(
			this.editableValue.startEditing(),
			false,
			'try to start edit mode again'
		);

		assert.equal(
			this.editableValue.validate( [ this.strings.invalid[0] ] ),
			false,
			'empty value not validated'
		);

		assert.equal(
			this.editableValue.validate( [this.strings.valid[0]] ),
			true,
			'validated input'
		);

		this.editableValue.setValue( this.strings.invalid[0] );

		assert.ok(
			this.editableValue.getValue() instanceof Array && this.editableValue.getValue()[0] === this.strings.invalid[0],
			'set empty value'
		);

		assert.equal(
			this.editableValue.isEmpty(),
			true,
			'editable value is empty'
		);

		assert.ok(
			this.editableValue.getValue() instanceof Array && this.editableValue.getInitialValue()[0] === this.strings.valid[1],
			'checked initial value'
		);

		assert.equal(
			this.editableValue.valueCompare( this.editableValue.getValue(), this.editableValue.getInitialValue() ),
			false,
			'compared current and initial value'
		);

		this.editableValue.setValue( this.strings.valid[1] );

		assert.ok(
			this.editableValue.getValue() == this.strings.valid[1],
			'reset value to initial value'
		);

		assert.equal(
			this.editableValue.valueCompare( this.editableValue.getValue(), this.editableValue.getInitialValue() ),
			true,
			'compared current and initial value'
		);

		this.editableValue.remove();

	} );

	QUnit.test( 'error handling', function( assert ) {
		this.editableValue.simulateApiFailure();

		this.editableValue.startEditing();
		this.editableValue.setValue( this.strings.valid[0] );

		assert.equal(
			this.editableValue.isInEditMode(),
			true,
			'started editing ans set value'
		);

		this.editableValue.stopEditing( true );

		assert.equal(
			this.editableValue.isInEditMode(),
			true,
			'is still in edit mode after receiving error'
		);

		assert.ok(
			this.editableValue._toolbar.editGroup.btnSave._tooltip instanceof wb.ui.Tooltip,
			'attached tooltip to save button'
		);

		this.editableValue.simulateApiSuccess();

		this.editableValue.stopEditing();

		assert.equal(
			this.editableValue.isInEditMode(),
			false,
			'cancelled editing'
		);

		this.editableValue.simulateApiFailure();

		this.editableValue.preserveEmptyForm = true;
		this.editableValue.remove();

		assert.equal(
			this.editableValue.getValue()[0],
			this.editableValue.getInitialValue()[0],
			'emptied input interface resetting to default value and preserving the input interface'
		);

		this.editableValue.preserveEmptyForm = false;
		this.editableValue.remove();

		assert.ok(
			this.editableValue._toolbar.editGroup.btnRemove.getTooltip() instanceof wb.ui.Tooltip,
			'attached tooltip to remove button after trying to remove with API action'
		);

		this.editableValue.simulateApiFailure( 1 );
		this.editableValue.startEditing();
		this.editableValue.setValue( this.strings.valid[0] );
		this.editableValue.stopEditing( true );
		assert.equal(
			this.editableValue._toolbar.editGroup.btnSave._tooltip._error.shortMessage,
			mw.msg( 'wikibase-error-save-generic' ),
			"when getting unknown error-code from API, generic message should be shown"
		);

		this.editableValue.simulateApiFailure( 2 );
		this.editableValue.startEditing();
		this.editableValue.setValue( this.strings.valid[0] );
		this.editableValue.stopEditing( true );
		assert.equal(
			this.editableValue._toolbar.editGroup.btnSave._tooltip._error.shortMessage,
			mw.msg( 'wikibase-error-ui-client-error' ),
			"when getting an registered error-code from API, the corresponding message should be shown"
		);

	} );

}( mediaWiki, wikibase, jQuery, QUnit ) );
