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
	 * this is true if the input interface is initialized at the time.
	 * @var bool
	 */
	_isInEditMode: false,
	
	/**
	 * The toolbar controling the editable value
	 * @var: window.wikibase.ui.PropertyEditTool.Toolbar
	 */
	_toolbar: null,
			
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

		// use toolbar events to control the editable value:
		var self = this;
		this._toolbar.onActionEdit   = function(){ self.startEditing(); };
		//this._toolbar.onActionEdit   = jQuery.proxy( this.startEditing, this );
		this._toolbar.onActionSave   = function(){ self.stopEditing( true ); };
		this._toolbar.onActionCancel = function(){ self.stopEditing( false ); };
		
		if( this.isEmpty() ) {
			// enable editing from the beginning if there is no value yet!
			this._toolbar.doEdit();
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

		initText = this.getValue();
		
		inputBox = $( '<input/>', {
			'class': this.UI_CLASS,
			'type': 'text',
			'name': this._key,
			'value': initText,
			'placeholder': this.inputPlaceholder,
			'keypress': jQuery.proxy( this.keyPressed, this ),
			'keyup': jQuery.proxy( this.keyPressed, this )	// for escape key browser compability
		} );
		
		this._subject.text( '' );
		this._subject.append( inputBox );
		
		// store original text value from before input box insertion:
		inputBox.data( this.UI_CLASS + '-initial-value', initText );

        this._isInEditMode = true;
        inputBox.focus();
		return true;
	},
	
	/**
	 * Called when a key is pressed inside the input box
	 * 
	 */
	keyPressed: function( event ) {
		if( event.which == 13 ) {
			this._toolbar.doSave();
		} else if( event.which == 27 ) {
			this._toolbar.doCancel();
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
		var value = '';
		if( this.isInEditMode() ) {
			value = $( this._subject.children( '.' + this.UI_CLASS )[0] ).attr( 'value' );
		} else {
			value = this._subject.text();
		}
		return $.trim( value );
	},
	
	/**
	 * Returns true if there is currently no value assigned
	 *
	 * @return bool
	 */
	isEmpty: function() {
		return this.getValue() === '';
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