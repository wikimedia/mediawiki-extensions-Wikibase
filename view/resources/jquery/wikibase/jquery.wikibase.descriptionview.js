/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, mw, wb ) {
	'use strict';

	var PARENT = $.ui.TemplatedWidget;

/**
 * Manages a description.
 * @since 0.5
 * @extends jQuery.ui.TemplatedWidget
 *
 * @option {wikibase.datamodel.Term} value
 *
 * @option {string} [inputNodeName='TEXTAREA']
 *         Should either be 'TEXTAREA' or 'INPUT'.
 */
$.widget( 'wikibase.descriptionview', PARENT, {
	/**
	 * @see jQuery.ui.TemplatedWidget.options
	 */
	options: {
		template: 'wikibase-descriptionview',
		templateParams: [
			'', // additional class
			'', // text
			'' // toolbar
		],
		templateShortCuts: {
			$text: '.wikibase-descriptionview-text'
		},
		value: null,
		inputNodeName: 'TEXTAREA'
	},

	/**
	 * @type {boolean}
	 */
	_isInEditMode: false,

	/**
	 * @see jQuery.ui.TemplatedWidget._create
	 *
	 * @throws {Error} if required parameters are not specified properly.
	 */
	_create: function() {
		if ( !( this.options.value instanceof wb.datamodel.Term )
			|| this.options.inputNodeName !== 'INPUT' && this.options.inputNodeName !== 'TEXTAREA'
		) {
			throw new Error( 'Required parameter(s) missing' );
		}

		var self = this;

		this.element
		.on(
			'descriptionviewafterstartediting.' + this.widgetName
			+ ' eachchange.' + this.widgetName,
		function( event ) {
			if ( self.value().getText() === '' ) {
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

		if ( this.$text.text() === '' ) {
			this._draw();
		}
	},

	/**
	 * @see jQuery.ui.TemplatedWidget.destroy
	 */
	destroy: function() {
		if ( this._isInEditMode ) {
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
			descriptionText = this.options.value.getText();

		if ( descriptionText === '' ) {
			descriptionText = null;
		}

		this.element[descriptionText ? 'removeClass' : 'addClass']( 'wb-empty' );

		if ( !this._isInEditMode && !descriptionText ) {
			this.$text.text( mw.msg( 'wikibase-description-empty' ) );
			// Apply lang and dir of UI language
			// instead language of that row
			var userLanguage = mw.config.get( 'wgUserLanguage' );
			this.element
			.attr( 'lang', userLanguage )
			.attr( 'dir', $.util.getDirectionality( userLanguage ) );
			return;
		}

		this.element
		.attr( 'lang', languageCode )
		.attr( 'dir', $.util.getDirectionality( languageCode ) );

		if ( !this._isInEditMode ) {
			this.$text.text( descriptionText );
			return;
		}

		var $input = $( document.createElement( this.options.inputNodeName ) );

		$input
		.addClass( this.widgetFullName + '-input' )
		// TODO: Inject correct placeholder via options
		.attr( 'placeholder', mw.msg(
				'wikibase-description-edit-placeholder-language-aware',
				wb.getLanguageNameByCode( languageCode )
			)
		)
		.attr( 'lang', languageCode )
		.attr( 'dir', $.util.getDirectionality( languageCode ) )
		.on( 'keydown.' + this.widgetName, function( event ) {
			if ( event.keyCode === $.ui.keyCode.ENTER ) {
				event.preventDefault();
			}
		} )
		.on( 'eachchange.' + this.widgetName, function( event ) {
			self._trigger( 'change' );
		} );

		if ( descriptionText ) {
			$input.val( descriptionText );
		}

		if ( $.fn.inputautoexpand ) {
			$input.inputautoexpand( {
				expandHeight: true,
				suppressNewLine: true
			} );
		}

		this.$text.empty().append( $input );
	},

	/**
	 * Starts the widget's edit mode.
	 */
	startEditing: function() {
		if ( this._isInEditMode ) {
			return;
		}
		this.element.addClass( 'wb-edit' );
		this._isInEditMode = true;
		this._draw();
		this._trigger( 'afterstartediting' );
	},

	/**
	 * Stops the widget's edit mode.
	 *
	 * @param {boolean} dropValue
	 */
	stopEditing: function( dropValue ) {
		if ( !this._isInEditMode ) {
			return;
		} else if ( ( !this.isValid() || this.isInitialValue() ) && !dropValue ) {
			return;
		} else if ( dropValue ) {
			this._afterStopEditing( dropValue );
			return;
		}

		this.disable();

		this._trigger( 'stopediting', null, [dropValue] );

		this.enable();
		this._afterStopEditing( dropValue );
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
		if ( !dropValue ) {
			this.options.value = this.value();
		} else if ( this.options.value.getText() === '' ) {
			this.$text.children( '.' + this.widgetFullName + '-input' ).val( '' );
		}

		this.element.removeClass( 'wb-edit' );
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
		if ( !this._isInEditMode ) {
			return true;
		}

		return this.value().equals( this.options.value );
	},

	/**
	 * Toggles error state.
	 *
	 * @param {Error} error
	 */
	setError: function( error ) {
		if ( error ) {
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
		if ( key === 'value' && !( value instanceof wb.datamodel.Term ) ) {
			throw new Error( 'Value needs to be a wb.datamodel.Term instance' );
		}

		var response = PARENT.prototype._setOption.call( this, key, value );

		if ( key === 'disabled' && this._isInEditMode ) {
			this.$text.children( '.' + this.widgetFullName + '-input' ).prop( 'disabled', value );
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
		if ( value !== undefined ) {
			return this.option( 'value', value );
		}

		if ( !this._isInEditMode ) {
			return this.options.value;
		}

		return new wb.datamodel.Term(
			this.options.value.getLanguageCode(),
			$.trim( this.$text.children( '.' + this.widgetFullName + '-input' ).val() )
		);
	},

	/**
	 * @see jQuery.ui.TemplatedWidget.focus
	 */
	focus: function() {
		if ( this._isInEditMode ) {
			this.$text.children( '.' + this.widgetFullName + '-input' ).focus();
		} else {
			this.element.focus();
		}
	}

} );

}( jQuery, mediaWiki, wikibase ) );
