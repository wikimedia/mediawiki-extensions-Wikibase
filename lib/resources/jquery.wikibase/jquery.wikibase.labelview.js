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

		this.element.attr( 'id', 'wb-firstHeading-' + this.options.entityId );

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

		var dir = ( $.uls && $.uls.data ) ?
			$.uls.data.getDir( languageCode ) :
			$( 'html' ).prop( 'dir' );

		var $input = $( '<input />', {
			// TODO: Inject correct placeholder via options
			placeholder: mw.msg(
				'wikibase-label-edit-placeholder-language-aware',
				wb.getLanguageNameByCode( languageCode )
			),
			dir: dir
		} )
		.on( 'eachchange.' + this.widgetName, function( event ) {
			self._trigger( 'change' );
		} );

		if( labelText ) {
			$input.val( labelText );
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

$.wikibase.toolbarcontroller.definition( 'edittoolbar', {
	id: 'labelview',
	selector: '.wikibase-labelview:not(.wb-terms-label)',
	events: {
		labelviewcreate: function( event, toolbarcontroller ) {
			var $labelview = $( event.target ),
				labelview = $labelview.data( 'labelview' ),
				$container = $labelview.find( '.wikibase-toolbar-container' );

			// TODO: Remove toolbar-wrapper that is firstHeading specific (required to reset font
			// size)
			if( !$container.length ) {
				$container = $( '<span/>' ).appendTo(
					mw.wbTemplate( 'wikibase-toolbar-wrapper', '' )
					.appendTo( $labelview.find( '.wikibase-labelview-container' ) )
				);
			}

			$labelview.edittoolbar( {
				$container: $container,
				interactionWidget: labelview
			} );

			$labelview.on( 'keyup', function( event ) {
				if( labelview.option( 'disabled' ) ) {
					return;
				}
				if( event.keyCode === $.ui.keyCode.ESCAPE ) {
					labelview.stopEditing( true );
				} else if( event.keyCode === $.ui.keyCode.ENTER ) {
					labelview.stopEditing( false );
				}
			} );

			if( labelview.value().getText() === '' ) {
				labelview.toEditMode();
				$labelview.data( 'edittoolbar' ).toEditMode();
				$labelview.data( 'edittoolbar' ).disable();
			}

			$labelview
			.off( 'labelviewafterstopediting.edittoolbar' )
			.on( 'labelviewafterstopediting.edittoolbar', function( event ) {
				var edittoolbar = $( event.target ).data( 'edittoolbar' );
				if( labelview.value().getText() !== '' ) {
					edittoolbar.toNonEditMode();
					edittoolbar.enable();
					edittoolbar.toggleActionMessage( function() {
						edittoolbar.getButton( 'edit' ).focus();
					} );
				} else {
					labelview.toEditMode();
					edittoolbar.toEditMode();
					edittoolbar.toggleActionMessage( function() {
						labelview.focus();
					} );
					edittoolbar.disable();
				}
			} );
		},
		'labelviewchange labelviewafterstartediting labelviewafterstopediting': function( event ) {
			var $labelview = $( event.target ),
				labelview = $labelview.data( 'labelview' ),
				edittoolbar = $labelview.data( 'edittoolbar' ),
				btnSave = edittoolbar.getButton( 'save' ),
				enableSave = labelview.isValid() && !labelview.isInitialValue(),
				btnCancel = edittoolbar.getButton( 'cancel' ),
				currentLabel = labelview.value().getText(),
				disableCancel = currentLabel === '' && labelview.isInitialValue();

			btnSave[enableSave ? 'enable' : 'disable']();
			btnCancel[disableCancel ? 'disable' : 'enable']();

			if( event.type === 'labelviewchange' ) {
				if( !labelview.isInitialValue() ) {
					labelview.startEditing();
				} else if( labelview.isInitialValue() && labelview.value().getText() === '' ) {
					labelview.cancelEditing();
				}
			}
		},
		labelviewdisable: function( event ) {
			var $labelview = $( event.target ),
				labelview = $labelview.data( 'labelview' ),
				edittoolbar = $labelview.data( 'edittoolbar' ),
				btnSave = edittoolbar.getButton( 'save' ),
				enable = labelview.isValid() && !labelview.isInitialValue(),
				currentLabel = labelview.value().getText();

			btnSave[enable ? 'enable' : 'disable']();

			if( labelview.option( 'disabled' ) || currentLabel !== '' ) {
				return;
			}

			if( currentLabel === '' ) {
				edittoolbar.disable();
			}
		},
		edittoolbaredit: function( event, toolbarcontroller ) {
			var $labelview = $( event.target ),
				labelview = $labelview.data( 'labelview' );

			if( !labelview ) {
				return;
			}

			labelview.focus();
		}
	}
} );

}( jQuery, mediaWiki, wikibase ) );
