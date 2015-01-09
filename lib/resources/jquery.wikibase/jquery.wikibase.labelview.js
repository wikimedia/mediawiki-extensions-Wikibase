/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, mw, wb ) {
	'use strict';

	var PARENT = $.ui.TemplatedWidget;

/**
 * Manages a label.
 * @since 0.5
 * @extends jQuery.ui.TemplatedWidget
 *
 * @option {wikibase.datamodel.Term} value
 *
 * @option {string} [helpMessage]
 *         Default: mw.msg( 'wikibase-label-input-help-message' )
 *
 * @option {wikibase.entityChangers.LabelsChanger} labelsChanger
 *
 * @option {string} entityId
 *
 * @option {boolean} [showEntityId]
 *         Default: false
 */
$.widget( 'wikibase.labelview', PARENT, {
	/**
	 * @see jQuery.ui.TemplatedWidget.options
	 */
	options: {
		template: 'wikibase-labelview',
		templateParams: [
			'', // additional class
			'', // text
			'', // entity id
			'' // toolbar
		],
		templateShortCuts: {
			'$text': '.wikibase-labelview-text',
			'$entityId': '.wikibase-labelview-entityid'
		},
		value: null,
		helpMessage: mw.msg( 'wikibase-label-input-help-message' ),
		entityId: null,
		showEntityId: false
	},

	/**
	 * @type {boolean}
	 */
	_isInEditMode: false,

	/**
	 * @type {boolean}
	 */
	_isBeingEdited: false,

	/**
	 * @see jQuery.ui.TemplatedWidget._create
	 *
	 * @throws {Error} if required parameters are not specified properly.
	 */
	_create: function() {
		if(
			!( this.options.value instanceof wb.datamodel.Term )
			|| !this.options.entityId
			|| !this.options.labelsChanger
		) {
			throw new Error( 'Required parameter(s) missing' );
		}

		var self = this;

		this.element
		.on(
			'labelviewafterstartediting.' + this.widgetName
			+ ' eachchange.' + this.widgetName,
		function( event ) {
			if( self.value().getText() === '' ) {
				// Since the widget shall not be in view mode when there is no value, triggering
				// the event without a proper value is only done when creating the widget. Disabling
				// other edit buttons shall be avoided.
				// TODO: Move logic to a sensible place.
				self.element.addClass( 'wb-empty' );
				return;
			}

			self.element.removeClass( 'wb-empty' );
		} );

		PARENT.prototype._create.call( this );

		if( this.$text.text() === '' ) {
			this._draw();
		}
	},

	/**
	 * @see jQuery.ui.TemplatedWidget.destroy
	 */
	destroy: function() {
		if( this._isInEditMode ) {
			var self = this;

			this.element.one( this.widgetEventPrefix + 'afterstopediting', function( event ) {
				PARENT.prototype.destroy.call( self );
			} );

			this.cancelEditing();
		} else {
			PARENT.prototype.destroy.call( this );
		}
	},

	/**
	 * Main draw routine.
	 */
	_draw: function() {
		var self = this,
			languageCode = this.options.value.getLanguageCode(),
			labelText = this.options.value.getText();

		if( labelText === '' ) {
			labelText = null;
		}

		if( this.options.showEntityId && !( this._isInEditMode && labelText ) ) {
			this.$entityId.text( mw.msg( 'parentheses', this.options.entityId ) );
		} else {
			this.$entityId.empty();
		}

		this.element[labelText ? 'removeClass' : 'addClass']( 'wb-empty' );

		if( !this._isInEditMode ) {
			this.$text.text( labelText || mw.msg( 'wikibase-label-empty' ) );
			return;
		}

		var $input = $( '<input />' );

		$input
		.addClass( this.widgetFullName + '-input' )
		// TODO: Inject correct placeholder via options
		.attr( 'placeholder', mw.msg(
				'wikibase-label-edit-placeholder-language-aware',
				wb.getLanguageNameByCode( languageCode )
			)
		)
		.attr( 'dir', $.util.getDirectionality( languageCode ) )
		.on( 'eachchange.' + this.widgetName, function( event ) {
			self._trigger( 'change' );
		} );

		if( labelText ) {
			$input.val( labelText );
		}

		if( $.fn.inputautoexpand ) {
			$input.inputautoexpand();
		}

		this.$text.empty().append( $input );
	},

	/**
	 * Switches to editable state.
	 */
	toEditMode: function() {
		if( this._isInEditMode ) {
			return;
		}

		this._isInEditMode = true;
		this._draw();
	},

	/**
	 * Starts the widget's edit mode.
	 */
	startEditing: function() {
		if( this._isBeingEdited ) {
			return;
		}
		this.element.addClass( 'wb-edit' );
		this.toEditMode();
		this._isBeingEdited = true;
		this._trigger( 'afterstartediting' );
	},

	/**
	 * Stops the widget's edit mode.
	 *
	 * @param {boolean} dropValue
	 */
	stopEditing: function( dropValue ) {
		var self = this;

		dropValue = dropValue && this._isBeingEdited;

		if( !this._isInEditMode ) {
			return;
		} else if( ( !this.isValid() || this.isInitialValue() ) && !dropValue ) {
			return;
		} else if( dropValue ) {
			this._afterStopEditing( dropValue );
			return;
		}

		this.disable();

		this._trigger( 'stopediting', null, [dropValue] );

		this.options.labelsChanger.setLabel( this.value() )
		.done( function( label ) {
			self.enable();
			self._afterStopEditing( dropValue );
		} )
		.fail( function( error ) {
			self.setError( error );
			self.enable();
		} );
	},

	/**
	 * Cancels the widget's edit mode.
	 */
	cancelEditing: function() {
		this.stopEditing( true );
	},

	/**
	 * Callback tearing down edit mode.
	 *
	 * @param {boolean} dropValue
	 */
	_afterStopEditing: function( dropValue ) {
		if( !dropValue ) {
			this.options.value = this.value();
		} else if( this.options.value.getText() === '' ) {
			this.$text.children( 'input' ).val( '' );
		}

		this.element.removeClass( 'wb-edit' );
		this._isBeingEdited = false;
		this._isInEditMode = false;
		this._draw();

		this._trigger( 'afterstopediting', null, [dropValue] );
	},

	/**
	 * @return {boolean}
	 */
	isValid: function() {
		// Function is required by edittoolbar definition.
		return true;
	},

	/**
	 * @return {boolean}
	 */
	isInitialValue: function() {
		return this.value().equals( this.options.value );
	},

	/**
	 * Toggles error state.
	 *
	 * @param {Error} error
	 */
	setError: function( error ) {
		if( error ) {
			this.element.addClass( 'wb-error' );
			this._trigger( 'toggleerror', null, [error] );
		} else {
			this.removeError();
			this._trigger( 'toggleerror' );
		}
	},

	removeError: function() {
		this.element.removeClass( 'wb-error' );
	},

	/**
	 * @see jQuery.ui.TemplatedWidget._setOption
	 */
	_setOption: function( key, value ) {
		if( key === 'value' && !( value instanceof wb.datamodel.Term ) ) {
			throw new Error( 'Value needs to be a wb.datamodel.Term instance' );
		}

		var response = PARENT.prototype._setOption.call( this, key, value );

		if( key === 'disabled' && this._isInEditMode ) {
			this.$text.children( 'input' ).prop( 'disabled', value );
		}

		return response;
	},

	/**
	 * Gets/Sets the widget's value.
	 *
	 * @param {wikibase.datamodel.Term} [value]
	 * @return {wikibase.datamodel.Term|undefined}
	 */
	value: function( value ) {
		if( value !== undefined ) {
			this.option( 'value', value );
			return;
		}

		if( !this._isInEditMode ) {
			return this.option( 'value' );
		}

		return new wb.datamodel.Term(
			this.options.value.getLanguageCode(),
			$.trim( this.$text.children( 'input' ).val() )
		);
	},

	/**
	 * @see jQuery.ui.TemplatedWidget.focus
	 */
	focus: function() {
		if( this._isInEditMode ) {
			this.$text.children( 'input' ).focus();
		} else {
			this.element.focus();
		}
	}

} );

}( jQuery, mediaWiki, wikibase ) );
