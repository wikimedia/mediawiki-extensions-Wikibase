/**
 * @licence GNU GPL v2+
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
 * @option {string} [helpMessage]
 *         Default: mw.msg( 'wikibase-description-input-help-message' )
 *
 * @option {wikibase.entityChangers.DescriptionsChanger} descriptionsChanger
 *
 * @option {wikibase.store.EntityStore} entityStore
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
			'$text': '.wikibase-descriptionview-text'
		},
		value: null,
		helpMessage: mw.msg( 'wikibase-description-input-help-message' ),
		descriptionsChanger: null
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
			|| !this.options.descriptionsChanger
		) {
			throw new Error( 'Required parameter(s) missing' );
		}

		var self = this;

		this.element
		.on(
			'descriptionviewafterstartediting.' + this.widgetName
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

		if( this.options.value.getText() !== '' && this.$text.text() === '' ) {
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
			descriptionText = this.options.value.getText();

		if( descriptionText === '' ) {
			descriptionText = null;
		}

		this.element[descriptionText ? 'removeClass' : 'addClass']( 'wb-empty' );

		if( !this._isInEditMode ) {
			this.$text.text( descriptionText || mw.msg( 'wikibase-description-empty' ) );
			return;
		}

		var dir = ( $.uls && $.uls.data ) ?
			$.uls.data.getDir( languageCode ) :
			$( 'html' ).prop( 'dir' );

		var $input = $( '<input />', {
			// TODO: Inject correct placeholder via options
			placeholder: mw.msg(
				'wikibase-description-edit-placeholder-language-aware',
				wb.getNativeLanguageNameByCode( languageCode )
			),
			dir: dir
		} )
		.on( 'eachchange.' + this.widgetName, function( event ) {
			self._trigger( 'change' );
		} );

		if( descriptionText ) {
			$input.val( descriptionText );
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

		this.options.descriptionsChanger.setDescription( this.value() )
		.done( function( description ) {
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
	 * Puts Keyboard focus on the widget.
	 */
	focus: function() {
		if( this._isInEditMode ) {
			this.$text.children( 'input' ).focus();
		}
	}

} );

$.wikibase.toolbarcontroller.definition( 'edittoolbar', {
	id: 'descriptionview',
	selector: '.wikibase-descriptionview:not(.wb-terms-description)',
	events: {
		descriptionviewcreate: function( event, toolbarcontroller ) {
			var $descriptionview = $( event.target ),
				descriptionview = $descriptionview.data( 'descriptionview' ),
				$container = $descriptionview.find( '.wikibase-toolbar-container' );

			if( !$container.length ) {
				$container = $( '<span/>' ).appendTo(
					$descriptionview.find( '.wikibase-descriptionview-container' )
				);
			}

			$descriptionview.edittoolbar( {
				$container: $container,
				interactionWidget: descriptionview
			} );

			$descriptionview.on( 'keyup', function( event ) {
				if( descriptionview.option( 'disabled' ) ) {
					return;
				}
				if( event.keyCode === $.ui.keyCode.ESCAPE ) {
					descriptionview.stopEditing( true );
				} else if( event.keyCode === $.ui.keyCode.ENTER ) {
					descriptionview.stopEditing( false );
				}
			} );

			if( descriptionview.value().getText() === '' ) {
				descriptionview.toEditMode();
				$descriptionview.data( 'edittoolbar' ).toEditMode();
				$descriptionview.data( 'edittoolbar' ).disable();
			}

			$descriptionview
			.off( 'descriptionviewafterstopediting.edittoolbar' )
			.on( 'descriptionviewafterstopediting.edittoolbar', function( event ) {
				var edittoolbar = $( event.target ).data( 'edittoolbar' );
				if( descriptionview.value().getText() !== '' ) {
					edittoolbar.toNonEditMode();
					edittoolbar.enable();
					edittoolbar.toggleActionMessage( function() {
						edittoolbar.getButton( 'edit' ).focus();
					} );
				} else {
					descriptionview.toEditMode();
					edittoolbar.toEditMode();
					edittoolbar.toggleActionMessage( function() {
						descriptionview.focus();
					} );
					edittoolbar.disable();
				}
			} );
		},
		'descriptionviewchange descriptionviewafterstartediting descriptionviewafterstopediting': function( event ) {
			var $descriptionview = $( event.target ),
				descriptionview = $descriptionview.data( 'descriptionview' ),
				edittoolbar = $descriptionview.data( 'edittoolbar' ),
				btnSave = edittoolbar.getButton( 'save' ),
				enableSave = descriptionview.isValid() && !descriptionview.isInitialValue(),
				btnCancel = edittoolbar.getButton( 'cancel' ),
				currentDescription = descriptionview.value().getText(),
				disableCancel = currentDescription === '' && descriptionview.isInitialValue();

			btnSave[enableSave ? 'enable' : 'disable']();
			btnCancel[disableCancel ? 'disable' : 'enable']();

			if( event.type === 'descriptionviewchange' ) {
				if( !descriptionview.isInitialValue() ) {
					descriptionview.startEditing();
				} else if(
					descriptionview.isInitialValue()
					&& descriptionview.value().getText() === ''
				) {
					descriptionview.cancelEditing();
				}
			}
		},
		descriptionviewdisable: function( event ) {
			var $descriptionview = $( event.target ),
				descriptionview = $descriptionview.data( 'descriptionview' ),
				edittoolbar = $descriptionview.data( 'edittoolbar' ),
				btnSave = edittoolbar.getButton( 'save' ),
				enable = descriptionview.isValid() && !descriptionview.isInitialValue(),
				currentDescription = descriptionview.value().getText();

			btnSave[enable ? 'enable' : 'disable']();

			if( descriptionview.option( 'disabled' ) || currentDescription !== '' ) {
				return;
			}

			if( currentDescription === '' ) {
				edittoolbar.disable();
			}
		},
		edittoolbaredit: function( event, toolbarcontroller ) {
			var $descriptionview = $( event.target ).closest( ':wikibase-edittoolbar' ),
				descriptionview = $descriptionview.data( 'descriptionview' );

			if( !descriptionview ) {
				return;
			}

			descriptionview.focus();
		}
	}
} );

}( jQuery, mediaWiki, wikibase ) );
