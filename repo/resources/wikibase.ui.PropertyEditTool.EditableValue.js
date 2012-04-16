/**
 * JavasSript for edit commands for 'Wikibase' property edit tool
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 * 
 * @since 0.1
 * @file wikibase.ui.PropertyEditTool.EditableValue.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */

/**
 * Serves the input interface for a value like a property value and also takes care of the conversion
 * between the pure html representation and the interface itself in both directions
 * 
 * @param jQuery parent
 */
window.wikibase.ui.PropertyEditTool.EditableValue = function( subject ) {
	if( typeof subject != 'undefined' ) {
		this._init( subject );
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
	 * Holds the parameters for the current API call
	 * @var Object
	 */
	_apiCall: null,
	
	/**
	 * Initializes the editable value.
	 * This should normally be called directly by the constructor.
	 */
	_init: function( subject ) {
		if( this._subject !== null ) {
			// initializing twice should never happen, have to destroy first!
			this.destroy();
		}
		this._subject = $( subject );
		this._initToolbar();
	},
	
	_initToolbar: function() {
		// TODO: If we want a separate toolbar for the label, we have to append and group the toolbar
		//       with the actual value perhaps.
		this._toolbar = new window.wikibase.ui.PropertyEditTool.Toolbar( this._subject.parent() );
		
		// give the toolbar a edit group with basic edit commands:
		var editGroup = new window.wikibase.ui.PropertyEditTool.Toolbar.EditGroup( this );
		this._toolbar.addElement( editGroup );
		this._toolbar.editGroup = editGroup; // remember this
		
		if( this.isEmpty() ) {
			// enable editing from the beginning if there is no value yet!
			this._toolbar.editGroup.btnEdit.doAction();
			this.removeFocus(); // but don't set focus there for now
		}
	},
	
	
	
	destroy: function() {
		// TODO implement on demand
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

		var initText = this.getValue();
		
		this._inputElem = $( '<input/>', {
			'class': this.UI_CLASS,
			'type': 'text',
			'name': this._key,
			'value': initText,
			'placeholder': this.inputPlaceholder,
			'keypress': jQuery.proxy( this._keyPressed, this ), // todo: this shouldn't be used, keyup should work fine!
			'keyup': jQuery.proxy( this._keyPressed, this ),	// for escape key browser compability
			'focus': jQuery.proxy( this._onFocus, this ),
			'blur': jQuery.proxy( this._onBlur, this )
		} );
		
		this._subject.text( '' );
		this._subject.append( this._inputElem );
		
		// store original text value from before input box insertion:
		this._inputElem.data( this.UI_CLASS + '-initial-value', initText );

        this._isInEditMode = true;
		
		this._inputRegistered(); // do this after setting _isInEditMode !
        this.setFocus();
		
		return true;
	},
	
	/**
	 * Called when the input changes in general for example on its initialization when setting
	 * its initial value.
	 */
	_inputRegistered: function() {
		var disableSave = this.isEmpty() || ( this.getInitialValue() === this.getValue() );
		var disableCancel = this.isEmpty() || ( ! this.validate( this.getInitialValue() ) );
		
		this._toolbar.editGroup.btnSave.setDisabled( disableSave );
		this._toolbar.editGroup.btnCancel.setDisabled( disableCancel );
		//this._toolbar.draw();
	},
	
	/**
	 * Called when a key is pressed inside the input interface
	 */
	_keyPressed: function( event ) {
		this._inputRegistered();
		
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
		this._subject.text( $value );
		
		this._isInEditMode = false;
		
		console.log( this._subject );
		
		if( save ) {
			this._apiCall = {
				action: "wbsetlabel", 
				language: wgUserLanguage, 
				label: this.getValue(), 
				id: mw.config.values.wbItemId
			};
			/*
			this._apiCall = {
				action: 'wbsetdescription', 
				language: wgUserLanguage, 
				description: this.getValue(), 
				id: mw.config.values.wbItemId
			};
			*/
			this.doApiLoad();
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
	 * load the mediawiki JS for APIs 
	 */
	doApiLoad: function() {
		mw.loader.using( 'mediawiki.api', jQuery.proxy( this.doApiCall, this ) );
	},
	
	/**
	 * makes the API call with the parameters stored in this._apiCall
	 */
	doApiCall: function() {
		console.log( this._apiCall );
		
		var localApi = new mw.Api();
		localApi.post( this._apiCall, {
			ok: jQuery.proxy( this.apiCallOk, this ),
			err: jQuery.proxy( this.apiCallErr, this )
		} );
	},
	
	/**
	 * handle return of successful API call
	 */
	apiCallOk: function() { 
		console.log( arguments ); 
	},
	
	/**
	 * handle error of unsuccessful API call
	 */
	apiCallErr: function() { 
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
	 * Returns the current value
	 * 
	 * @return string
	 */
	getValue: function() {		
		var value = '';
		if( this.isInEditMode() ) {
			value = $( this._subject.children( '.' + this.UI_CLASS )[0] ).attr( 'value' );
		} else {
			value = this._subject.text();
		}
		return $.trim( value );
	},
	
	/**
	 * If the input is in edit mode, this will return the value active before the edit mode was entered.
	 * If its not in edit mode, the current value will be returned.
	 * @return string
	 */
	getInitialValue: function() {
		if( ! this.isInEditMode() ) {
			return this._subject.text();
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


window.wikibase.ui.PropertyEditTool.EditableLabel = function( subject ) {
	window.wikibase.ui.PropertyEditTool.EditableValue.call( this, subject );
};
window.wikibase.ui.PropertyEditTool.EditableLabel.prototype = new window.wikibase.ui.PropertyEditTool.EditableValue();
$.extend( window.wikibase.ui.PropertyEditTool.EditableLabel.prototype, {
	getInputHelpMessage: function() {
		return window.mw.msg( 'wikibase-label-input-help-message' );
	}
} );


window.wikibase.ui.PropertyEditTool.EditableDescription = function( subject ) {
	window.wikibase.ui.PropertyEditTool.EditableValue.call( this, subject );
};
window.wikibase.ui.PropertyEditTool.EditableDescription.prototype = new window.wikibase.ui.PropertyEditTool.EditableValue();
$.extend( window.wikibase.ui.PropertyEditTool.EditableDescription.prototype, {
	getInputHelpMessage: function() {
		return window.mw.msg( 'wikibase-description-input-help-message' );
	}
} );
