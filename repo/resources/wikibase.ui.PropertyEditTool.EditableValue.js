/**
 * JavasSript for edit commands for 'Wikibase' property edit tool
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 * 
 * @since 0.1
 * @file wikibase.ui.PropertyEditTool.Toolbar.js
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
	 * this is true if the input interface is initialized at the time.
	 * @var bool
	 */
	_isInEditMode: false,
			
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
	},
	
	destroy: function() {
		// TODO
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

		initText = this.getValue();
		
		inputBox = $( '<input/>', {
			'class': this.UI_CLASS,
			'type': 'text',
			'name': this._key,
			'value': initText
		} );
		
		this._subject.text( '' );
		this._subject.append( inputBox );
		
		// store original text value from before input box insertion:
		inputBox.data( this.UI_CLASS + '-initial-value', initText );

        this._isInEditMode = true;
		return true;
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
		inputBox = $( this._subject.children( '.' + this.UI_CLASS )[0] );
		initialValue = inputBox.data( this.UI_CLASS + '-initial-value' );
		
		$value = ( ! save )
				? initialValue
				: this.getValue();
		
		inputBox.empty().remove(); // remove input interface		
		this._subject.text( $value );
		
		this._isInEditMode = false;
		
		// any change at all compared to initial value?
		return initialValue !== $value;
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
		if ( this.isInEditMode() ) {
			return $.trim ( $( this._subject.children( '.' + this.UI_CLASS )[0] ).attr( 'value' ) );
		} else {
			return $.trim( this._subject.text() );
		}
	}
};