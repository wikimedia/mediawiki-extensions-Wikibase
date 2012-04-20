/**
 * JavasSript for managing editable representation of property values.
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 * 
 * @since 0.1
 * @file wikibase.ui.PropertyEditTool.EditableValue.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
"use strict";

/**
 * Serves the input interface for a value like a property value and also takes care of the conversion
 * between the pure html representation and the interface itself in both directions
 * 
 * @param jQuery subject
 * @param wikibase.ui.PropertyEditTool.Toolbar toolbar
 */
window.wikibase.ui.PropertyEditTool.EditableValue = function( subject, toolbar ) {
	if( typeof subject != 'undefined' && typeof toolbar != 'undefined' ) {
		this._init( subject, toolbar );
	}
};
window.wikibase.ui.PropertyEditTool.EditableValue.prototype = {
	/**
	 * @const
	 * Class which marks the element within the site html.
	 */
	UI_CLASS: 'wb-ui-propertyedittoolbar-editablevalue',
	
	/**
	 * Element representing the editable value. This element will either hold the value or the input
	 * box in case it is activated for edit.
	 * @var jQuery
	 */
	_subject: null,
	
	/**
	 * This is true if the input interface is initialized at the time.
	 * @var bool
	 */
	_isInEditMode: false,
	
	/**
	 * Holds the input element in case this is in edit mode
	 * @var null|jQuery
	 */
	_inputElem: null,
	
	/**
	 * The toolbar controling the editable value
	 * @var: window.wikibase.ui.PropertyEditTool.Toolbar
	 */
	_toolbar: null,
	
	/**
	 * Initializes the editable value.
	 * This should normally be called directly by the constructor.
	 * 
	* @param jQuery subject
	* @param wikibase.ui.PropertyEditTool.Toolbar toolbar shouldn't be initialized yet
	 */
	_init: function( subject, toolbar ) {
		if( this._subject !== null ) {
			// initializing twice should never happen, have to destroy first!
			this.destroy();
		}
		this._subject = $( subject );
		this._toolbar = toolbar;
		
		this._toolbar.appendTo( this._getToolbarParent() );
		
		if( this.isEmpty() ) {
			// enable editing from the beginning if there is no value yet!
			this._toolbar.editGroup.btnEdit.doAction();
			this.removeFocus(); // but don't set focus there for now
		}
	},
	
	/**
	 * Returns the node the toolbar should be appended to
	 */
	_getToolbarParent: function() {
		return this._subject.parent();
	},

	remove: function() {
		// TODO API call
		this.doApiCall( true );
		this.destroy();
		this._subject.empty().remove();
	},

	destroy: function() {
		if( this._toolbar != null) {
			this._toolbar.destroy();
		}
		if( this._inputElem != null ) {
			this._inputElem.remove();
		}
	},

	/**
	 * By calling this, the editable value will be made editable for the user.
	 * Call stopEditing() to save or cancel the editing process.
	 * Basically this initializes the input box as sub element of the subject and uses the
	 * elements content as initial text.
	 *
	 * @return bool will return false if edit mode is active already.
	 */
	startEditing: function() {
		if( this.isInEditMode() ) {
			return false;
		}
		this._inputElem = this._buildInputElement();
		// store original text value from before input box insertion:
		this._inputElem.data( this.UI_CLASS + '-initial-value', this.getValue() );
		
		var inputParent = this._getValueContainer();
		inputParent.text( '' );
		this._inputElem.appendTo( inputParent );

        this._isInEditMode = true;

		this._onInputRegistered(); // do this after setting _isInEditMode !
        this.setFocus();

		return true;
	},
	
	/**
	 * returns the input element for editing
	 * @return jQuery
	 */
	_buildInputElement: function() {
		return $( '<input/>', {
			'class': this.UI_CLASS,
			'type': 'text',
			'name': this._key,
			'value': this.getValue(),
			'placeholder': this.inputPlaceholder,
			'keypress': jQuery.proxy( this._onKeyPressed, this ), // TODO: this shouldn't be used, keyup should work fine!
			'keyup': jQuery.proxy( this._onKeyPressed, this ),	//       we have both for escape key browser compability
			'focus': jQuery.proxy( this._onFocus, this ),
			'blur': jQuery.proxy( this._onBlur, this )
		} );
	},
	
	/**
	 * Returns the node holding the value. This node will also hold the input box when in edit mode.
	 * @return jQuery
	 */
	_getValueContainer: function() {
		return this._subject;
	},

	/**
	 * Called when the input changes in general for example on its initialization when setting
	 * its initial value.
	 */
	_onInputRegistered: function() {
		var value = this.getValue();
		var isInvalid = !this.validate( value );
		
		// can't save if invalid input OR same as before
		var disableSave = isInvalid || ( this.getInitialValue() === value );
		
		// can't cancel if empty before
		var disableCancel = this.getInitialValue() === '';

		this._toolbar.editGroup.btnSave.setDisabled( disableSave );
		this._toolbar.editGroup.btnCancel.setDisabled( disableCancel );
	},

	/**
	 * Called when a key is pressed inside the input interface
	 */
	_onKeyPressed: function( event ) {
		this._onInputRegistered();

		if( event.which == 13 ) {
			this._toolbar.editGroup.btnSave.doAction();
		}
		else if( event.which == 27 ) {
			this._toolbar.editGroup.btnCancel.doAction();
		}
	},

	_onFocus: function( event ) {
		this._toolbar.editGroup.tooltip.show( true );
	},
	_onBlur: function( event ) {
		this._toolbar.editGroup.tooltip.hide();
	},

	/**
	 * Destroys the edit box and displays the original text or the inputs new value.
	 *
	 * @param bool save whether to save the new user given value
	 * @return bool whether the value has changed compared to the original value
	 */
	stopEditing: function( save ) {
		if( ! this.isInEditMode() ) {
			return false;
		}
		var initialValue = this.getInitialValue();

		var $value = save ? this.getValue() : initialValue;

		this._inputElem.empty().remove(); // remove input interface
		this._inputElem = null;
		
		this._isInEditMode = false;
		this.setValue( $value );

		if( save ) {
			this.doApiCall( false );
		}

		// any change at all compared to initial value?
		return initialValue !== $value;
	},

	/**
	 * Sets the focus to the input interface
	 */
	setFocus: function() {
		if( this._inputElem !== null ) {
			this._inputElem.focus();
		}
	},

	/**
	 * Removes the focus from the input interface
	 */
	removeFocus: function() {
		if( this._inputElem !== null ) {
			this._inputElem.blur();
		}
	},

	/**
	 * Does the actual API call
	 * @param bool removeValue whether to make the remove or the save call to the API
	 */
	doApiCall: function( removeValue ) {
		var apiCall = this.getApiCallParams( removeValue );

		mw.loader.using( 'mediawiki.api', jQuery.proxy( function() {
			console.log( apiCall );

			var localApi = new mw.Api();
			localApi.post( apiCall, {
				ok: jQuery.proxy( this._apiCallOk, this ),
				err: jQuery.proxy( this._apiCallErr, this )
			} );
		}, this ) );
	},


	/**
	 * Returns the neccessary parameters for a api call to store the value.
	 */
	getApiCallParams: function() {
		return {};
	},

	/**
	 * handle return of successful API call
	 */
	_apiCallOk: function() {
		console.log( arguments );
	},

	/**
	 * handle error of unsuccessful API call
	 */
	_apiCallErr: function() {
		console.log( arguments );
	},

	/**
	 * Returns whether the input interface is loaded currently
	 *
	 * @return bool
	 */
	isInEditMode: function() {
		return this._isInEditMode;
	},

	/**
	 * // TODO: should return an object representing the properties value
	 * Returns the current value
	 *
	 * @return string
	 */
	getValue: function() {
		var value = '';
		if( this.isInEditMode() ) {
			value = $( this._getValueContainer().children( '.' + this.UI_CLASS )[0] ).attr( 'value' );
		} else {
			value = this._getValueContainer().text();
		}
		return $.trim( value );
	},
	
	/**
	 * Sets a value
	 */
	setValue: function( value ) {
		this._getValueContainer().text( value );
	},

	/**
	 * If the input is in edit mode, this will return the value active before the edit mode was entered.
	 * If its not in edit mode, the current value will be returned.
	 * @return string
	 */
	getInitialValue: function() {
		if( ! this.isInEditMode() ) {
			return this.getValue();
		}
		return this._inputElem.data( this.UI_CLASS + '-initial-value' );
	},

	/**
	 * Returns a short information about how the input should be inserted by the user.
	 */
	getInputHelpMessage: function() {
		return 'my message';
	},

	/**
	 * Returns true if there is currently no value assigned
	 *
	 * @return bool
	 */
	isEmpty: function() {
		return this.getValue() === '';
	},

	/**
	 * Velidates whether a certain value would be valid for this editable value.
	 *
	 * @todo: we might want to move this into a class describing the property/snak later.
	 *
	 * @param string text
	 * @return bool
	 */
	validate: function( value ) {
		return $.trim( value ) !== '';
	},

	/////////////////
	// CONFIGURABLE:
	/////////////////

	/**
	 * Allows to define a default value appearing in the input box in case there is no value given
	 * @var string
	 */
	inputPlaceholder: ''
};
