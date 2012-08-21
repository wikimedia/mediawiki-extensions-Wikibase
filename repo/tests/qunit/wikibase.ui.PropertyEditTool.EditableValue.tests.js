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
	module( 'wikibase.ui.PropertyEditTool.EditableValue', window.QUnit.newWbEnvironment( {
		setup: function() {
			var node = $( '<div/>', { id: 'parent' } );
			this.propertyEditTool = new window.wikibase.ui.PropertyEditTool( node );
			this.editableValue = new window.wikibase.ui.PropertyEditTool.EditableValue();

			var toolbar = this.propertyEditTool._buildSingleValueToolbar( this.editableValue );
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

			equal(
				this.editableValue._getToolbarParent().parent().attr( 'id' ),
				'parent',
				'parent node for toolbar exists'
			);

			equal(
				this.editableValue.getSubject()[0],
				node[0],
				'verified subject node'
			);

			ok(
				this.editableValue._interfaces.length == 1
					&& this.editableValue._interfaces[0] instanceof window.wikibase.ui.PropertyEditTool.EditableValue.Interface,
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

			this.editableValue.simulateApiFailure();

			ok(
				this.editableValue.remove().state() === 'rejected',
				'simulateApiFailure() we use for testing failures in the API works'
			);

			this.editableValue.simulateApiSuccess = function() {
				self.editableValue.queryApi = function( deferred, apiAction ) { // override AJAX API call
					deferred.resolve().promise();
				};
			};

			this.editableValue.simulateApiSuccess(); // initial state by default api actions in our tests are a success!

			ok(
				this.editableValue.save().state() === 'resolved',
				'simulateApiSuccess() we use for testing success in the API works'
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

			this.propertyEditTool.destroy();
			this.propertyEditTool = null;
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

		ok(
			this.editableValue.getToolbar() instanceof wikibase.ui.Toolbar,
			'instantiated toolbar'
		);

	} );


	test( 'dis- and enabling', function() {

		equal(
			this.editableValue.enable(),
			true,
			'enabling'
		);
		
		equal(
			this.editableValue.isEnabled(),
			true,
			'is enabled'
		);

		equal(
			this.editableValue.isDisabled(),
			false,
			'not disabled'
		);

		equal(
			this.editableValue.disable(),
			true,
			'disabling'
		);

		equal(
			this.editableValue.isDisabled(),
			true,
			'disabled'
		);

		equal(
			this.editableValue.isEnabled(),
			false,
			'not enabled'
		);

		equal(
			this.editableValue.getToolbar().enable(),
			true,
			'enabling toolbar'
		);

		equal(
			this.editableValue.getElementsState(),
			wikibase.ui.ELEMENT_STATE.MIXED,
			'mixed state'
		);

		equal(
			this.editableValue.isDisabled(),
			false,
			'not disabled'
		);

		equal(
			this.editableValue.isEnabled(),
			false,
			'not enabled'
		);

		equal(
			this.editableValue.enable(),
			true,
			'enabling'
		);

		equal(
			this.editableValue.isEnabled(),
			true,
			'is enabled'
		);

		equal(
			this.editableValue.isDisabled(),
			false,
			'is not disabled'
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
			this.editableValue.stopEditing( false ).promisor.apiAction,
			wikibase.ui.PropertyEditTool.EditableValue.prototype.API_ACTION.NONE,
			"stopped edit mode, don't save value"
		);

		ok(
			this.editableValue.getValue()[0] != this.strings['valid'][0],
			'value not saved after leaving edit mode without saving value'
		);

		var deferred = this.editableValue.stopEditing( false );

		equal(
			deferred.promisor.apiAction,
			wikibase.ui.PropertyEditTool.EditableValue.prototype.API_ACTION.NONE,
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
			this.editableValue.stopEditing( true ).promisor.apiAction,
			wikibase.ui.PropertyEditTool.EditableValue.prototype.API_ACTION.SAVE,
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

		this.editableValue.simulateApiFailure();

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
			this.editableValue._toolbar.editGroup.btnSave._tooltip instanceof window.wikibase.ui.Tooltip,
			'attached tooltip to save button'
		);

		this.editableValue.simulateApiSuccess();

		this.editableValue.stopEditing();

		equal(
			this.editableValue.isInEditMode(),
			false,
			'cancelled editing'
		);

		this.editableValue.simulateApiFailure();

		this.editableValue.preserveEmptyForm = true;
		this.editableValue.remove();

		equal(
			this.editableValue.getValue()[0],
			this.editableValue.getInitialValue()[0],
			'emptied input interface resetting to default value and preserving the input interface'
		);

		this.editableValue.preserveEmptyForm = false;
		this.editableValue.remove();

		ok(
			this.editableValue._toolbar.editGroup.btnRemove.getTooltip() instanceof window.wikibase.ui.Tooltip,
			'attached tooltip to remove button after trying to remove with API action'
		);

		this.editableValue.simulateApiFailure( 1 );
		this.editableValue.startEditing();
		this.editableValue.setValue( this.strings['valid'][0] );
		this.editableValue.stopEditing( true );
		equal(
			this.editableValue._toolbar.editGroup.btnSave._tooltip._error.shortMessage,
			mw.msg( 'wikibase-error-save-generic' ),
			"when getting unknown error-code from API, generic message should be shown"
		);

		this.editableValue.simulateApiFailure( 2 );
		this.editableValue.startEditing();
		this.editableValue.setValue( this.strings['valid'][0] );
		this.editableValue.stopEditing( true );
		equal(
			this.editableValue._toolbar.editGroup.btnSave._tooltip._error.shortMessage,
			mw.msg( 'wikibase-error-ui-client-error' ),
			"when getting an registered error-code from API, the corresponding message should be shown"
		);

	} );


}() );
