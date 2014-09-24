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
 * @option {Object|null} value
 *         Object representing the widget's value.
 *         Structure: { language: <{string}>, description: <{string|null}> }
 *
 * @option {string} [helpMessage]
 *         Default: mw.msg( 'wikibase-description-input-help-message' )
 *
 * @options {string} entityId
 *
 * @option {wikibase.RepoApi} api
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
		entityId: null,
		api: null
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
		if( !this.options.entityId || !this.options.api ) {
			throw new Error( 'Required parameter(s) missing' );
		}

		this.options.value = this._checkValue( this.options.value );

		var self = this,
			value = this.options.value;

		this.element
		.on(
			'descriptionviewafterstartediting.' + this.widgetName
			+ ' eachchange.' + this.widgetName,
		function( event ) {
			if( !self.value().description ) {
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

		if( value && value.description !== '' && this.$text.text() === '' ) {
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
		this.element[this.options.value.description ? 'removeClass' : 'addClass']( 'wb-empty' );

		if( !this._isInEditMode ) {
			this.$text.text(
				this.options.value.description || mw.msg( 'wikibase-description-empty' )
			);
			return;
		}

		var self = this;

		var $input = $( '<input/>' )
		// TODO: Inject correct placeholder via options
		.attr( 'placeholder', mw.msg(
			'wikibase-description-edit-placeholder-language-aware',
			wb.getLanguageNameByCode( this.options.value.language )
		) )
		.on( 'eachchange.' + this.widgetName, function( event ) {
			self._trigger( 'change' );
		} );

		if( this.options.value.description ) {
			$input.val( this.options.value.description );
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

		if( !this._isInEditMode || ( !this.isValid() || this.isInitialValue() ) && !dropValue ) {
			return;
		}

		if( dropValue ) {
			this._afterStopEditing( dropValue );
			return;
		}

		this.disable();

		this._trigger( 'stopediting', null, [dropValue] );

		// TODO: Performing API interaction should be managed in parent component (probably
		// entityview)
		this._save()
		.done( function( response ) {
			wb.getRevisionStore().setDescriptionRevision( response.entity.lastrevid );
			self.enable();
			self._afterStopEditing( dropValue );
		} )
		.fail( function( errorCode, details ) {
			// TODO: API should return an Error object
			var error = wb.RepoApiError.newFromApiResponse( details, 'save' );
			self.setError( error );
			self.enable();
		} );
	},

	/**
	 * @return {jQuery.Promise}
	 */
	_save: function() {
		return this.options.api.setDescription(
			this.options.entityId,
			wb.getRevisionStore().getDescriptionRevision(),
			this.value().description || '',
			this.options.value.language
		);
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
		} else if( !this.options.value.description ) {
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
		var initialValue = this.options.value,
			currentValue = this.value();

		return currentValue.language === initialValue.language
			&& currentValue.description === initialValue.description;
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
			this.element.removeClass( 'wb-error' );
			this._trigger( 'toggleerror' );
		}
	},

	/**
	 * @see jQuery.ui.TemplatedWidget._setOption
	 */
	_setOption: function( key, value ) {
		if( key === 'value' ) {
			value = this._checkValue( value );
		}
		return PARENT.prototype._setOption.call( this, key, value );
	},

	/**
	 * @param {*} value
	 * @return {Object}
	 *
	 * @throws {Error} if value is not defined properly.
	 */
	_checkValue: function( value ) {
		if( !$.isPlainObject( value ) ) {
			throw new Error( 'Value needs to be an object' );
		} else if( !value.language ) {
			throw new Error( 'Value needs language to be specified' );
		}

		if( !value.description ) {
			value.description = null;
		}

		return value;
	},

	/**
	 * Gets/Sets the widget's value.
	 *
	 * @param {Object} [value]
	 * @return {Object|undefined}
	 */
	value: function( value ) {
		if( value !== undefined ) {
			this.option( 'value', value );
			return;
		}

		if( !this._isInEditMode ) {
			return this.option( 'value' );
		}

		var text = $.trim( this.$text.children( 'input' ).val() );

		return {
			language: this.options.value.language,
			description: text !== '' ? text : null
		};
	},

	/**
	 * Puts Keyboard focus on the widget.
	 */
	focus: function() {
		if( this._isInEditMode ) {
			this.$text.children( 'input' ).focus();
		}
	},

	/**
	 * @see jQuery.ui.TemplatedWidget.disable
	 */
	disable: function() {
		if( this._isInEditMode ) {
			this.$text.children( 'input' ).prop( 'disabled', true );
		}

		return PARENT.prototype.disable.call( this );
	},

	/**
	 * @see jQuery.ui.TemplatedWidget.enable
	 */
	enable: function() {
		if( this._isInEditMode ) {
			this.$text.children( 'input' ).prop( 'disabled', false );
		}

		return PARENT.prototype.enable.call( this );
	}

} );

$.wikibase.toolbarcontroller.definition( 'edittoolbar', {
	id: 'descriptionview',
	selector: '.wikibase-descriptionview:not(.wb-terms-description)',
	events: {
		descriptionviewcreate: function( event, toolbarcontroller ) {
			var $descriptionview = $( event.target ),
				descriptionview = $descriptionview.data( 'descriptionview' );

			$descriptionview.edittoolbar( {
				$container: $descriptionview.find( '.wikibase-descriptionview-container' ),
				interactionWidgetName: $.wikibase.descriptionview.prototype.widgetName,
				enableRemove: false
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

			if( !descriptionview.value().description ) {
				descriptionview.toEditMode();
				$descriptionview.data( 'edittoolbar' ).toolbar.editGroup.toEditMode();
				$descriptionview.data( 'edittoolbar' ).toolbar.editGroup.disable();
			}

			$descriptionview
			.off( 'descriptionviewafterstopediting.edittoolbar' )
			.on( 'descriptionviewafterstopediting', function( event ) {
				var edittoolbar = $( event.target ).data( 'edittoolbar' );
				if( descriptionview.value().description ) {
					edittoolbar.toolbar.editGroup.toNonEditMode();
					edittoolbar.enable();
					edittoolbar.toggleActionMessage( function() {
						edittoolbar.toolbar.editGroup.getButton( 'edit' ).focus();
					} );
				} else {
					descriptionview.toEditMode();
					edittoolbar.toolbar.editGroup.toEditMode();
					edittoolbar.toggleActionMessage( function() {
						descriptionview.focus();
					} );
					$descriptionview.data( 'edittoolbar' ).toolbar.editGroup.disable();
				}
			} );
		},
		'descriptionviewchange descriptionviewafterstartediting descriptionviewafterstopediting': function( event ) {
			var $descriptionview = $( event.target ),
				descriptionview = $descriptionview.data( 'descriptionview' ),
				toolbar = $descriptionview.data( 'edittoolbar' ).toolbar,
				$btnSave = toolbar.editGroup.getButton( 'save' ),
				btnSave = $btnSave.data( 'toolbarbutton' ),
				enable = descriptionview.isValid() && !descriptionview.isInitialValue(),
				$btnCancel = toolbar.editGroup.getButton( 'cancel' ),
				btnCancel = $btnCancel.data( 'toolbarbutton' ),
				currentDescription = descriptionview.value().description,
				disableCancel = !currentDescription && descriptionview.isInitialValue();

			btnSave[enable ? 'enable' : 'disable']();
			btnCancel[disableCancel ? 'disable' : 'enable']();

			if( event.type === 'descriptionviewchange' ) {
				if( !descriptionview.isInitialValue() ) {
					descriptionview.startEditing();
				} else if( descriptionview.isInitialValue() && !descriptionview.value().description ) {
					descriptionview.cancelEditing();
				}
			}
		},
		descriptionviewdisable: function( event ) {
			var $descriptionview = $( event.target ),
				descriptionview = $descriptionview.data( 'descriptionview' ),
				toolbar = $descriptionview.data( 'edittoolbar' ).toolbar,
				$btnSave = toolbar.editGroup.getButton( 'save' ),
				btnSave = $btnSave.data( 'toolbarbutton' ),
				enable = descriptionview.isValid() && !descriptionview.isInitialValue(),
				currentDescription = descriptionview.value().description;

			btnSave[enable ? 'enable' : 'disable']();

			if( descriptionview.option( 'disabled' ) || currentDescription ) {
				return;
			}

			if( !currentDescription ) {
				toolbar.disable();
			}
		},
		toolbareditgroupedit: function( event, toolbarcontroller ) {
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
