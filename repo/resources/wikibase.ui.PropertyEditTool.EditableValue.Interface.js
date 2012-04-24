/**
 * JavasSript for a part of an editable property value
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 * 
 * @since 0.1
 * @file wikibase.ui.PropertyEditTool.EditableValue.Interface.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
"use strict";

/**
 * Serves the input interface for a part of a value like a property value and also takes care of the
 * conversion between the pure html representation and the interface itself in both directions
 * 
 * @param jQuery subject
 */
window.wikibase.ui.PropertyEditTool.EditableValue.Interface = function( editableValue, subject ) {
	if( typeof editableValue != 'undefined' && typeof subject != 'undefined' ) {
		this._init( editableValue, subject );
	}
};
window.wikibase.ui.PropertyEditTool.EditableValue.Interface.prototype = {
	/**
	 * @const
	 * Class which marks the element within the site html.
	 */
	UI_CLASS: 'wb-ui-propertyedittoolbar-editablevaluepiece',

	/**
	 * Reference to parent editableValue
	 * @var wikibase.ui.PropertyEditTool.EditableValue
	 */
	_editableValue: null,

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
	 * Initializes the editable value.
	 * This should normally be called directly by the constructor.
	 * 
	 * @param jQuery subject
	 */
	_init: function( editableValue, subject ) {
		if( this._subject !== null ) {
			// initializing twice should never happen, have to destroy first!
			this.destroy();
		}
		this._subject = $( subject );
		this._editableValue = editableValue;
	},

	destroy: function() {
		if( this._isInEditMode ) {
			this.stopEditing( false );
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

		if ( this.autoExpand ) {
			var ruler = $( '<span/>', {
				'class': 'ruler'
			} );
			this._inputElem.after( ruler );
		}

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
			'keyup':    jQuery.proxy( this._onKeyPressed, this ), //       we have both for escape key browser compability
			'keydown':  jQuery.proxy( this._onKeyDown, this ),
			'focus':    jQuery.proxy( this._onFocus, this ),
			'blur':     jQuery.proxy( this._onBlur, this )
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
		if( this.onInputRegistered !== null && this.onInputRegistered() === false ) { // callback
			return false; // cancel
		}
	},

	_expand: function() {
		if ( this.autoExpand ) {
			var ruler = this._subject.find( '.ruler' );
			//console.log( '"'+ this._inputElem.attr( 'value' ).replace(/ /g, '&nbsp;') + '"');
			ruler.html( this._inputElem.attr( 'value' ).replace(/ /g, '&nbsp;') );
			var inputWidth = parseInt( this._inputElem.width() );

			//if ( this._inputElem.width() > $('#content').width() ) {
			//	this._inputElem.css( 'width', $('#content').width() + 'px' );
			//}

			this._inputElem.css( 'width', ( parseInt( ruler.width() ) + 25 ) + 'px' ); // TODO better resize mechanism (maybe by temporarily replacing text input)
			if ( typeof this._editableValue._toolbar._items[0].tooltip._tipsy.$tip != 'undefined' ) {
				var tooltipLeft = parseInt( this._editableValue._toolbar._items[0].tooltip._tipsy.$tip.css( 'left' ) );
				this._editableValue._toolbar._items[0].tooltip._tipsy.$tip.css( 'left', ( tooltipLeft + parseInt( this._inputElem.width() ) - inputWidth ) + 'px' );
			}
		}
	},

	/**
	 * Called when a key is pressed inside the input interface
	 */
	_onKeyPressed: function( event ) {
		this._onInputRegistered(); // TODO: do not fire this if input hasn't changed
		this._expand();
		if( this.onKeyPressed !== null && this.onKeyPressed( event ) === false ) { // callback
			return false; // cancel
		}
	},

	_onKeyDown: function( event ) {
		if( this.onKeyDown !== null && this.onKeyDown( event ) === false ) { // callback
			return false; // cancel
		}
	},

	_onFocus: function( event ) {
		this._expand();
		if( this.onFocus !== null ) {
			this.onFocus( event ); // callback
		}
	},
	_onBlur: function( event ) {
		if( this.onBlur !== null ) {
			this.onBlur( event ); // callback
		}
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
	inputPlaceholder: '',

	/**
	 * when true, automatically expands width of input element according to containing text
	 * @var bool
	 */
	autoExpand: false,

	///////////
	// EVENTS:
	///////////

	/**
	 * Callback called when the input changes in general for example on its initialization when
	 * setting its initial value.
	 */
	onInputRegistered: null,
	
	onKeyPressed: null,

	onKeyDown: null,
	
	onFocus: null,
	
	onBlur: null
};
