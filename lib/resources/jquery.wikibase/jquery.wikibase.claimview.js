/**
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, wb, $ ) {
	'use strict';

	var PARENT = $.TemplatedWidget;

/**
 * View for displaying and editing Wikibase Claims.
 * @since 0.3
 *
 * @option value {wb.Claim|null} The claim displayed by this view. This can only be set initially,
 *         the value function doesn't work as a setter in this view. If this is null, this view will
 *         start in edit mode, allowing the user to define the claim.
 *
 * @option predefined {Object} Allows to pre-define certain aspects of the Claim to be created.
 *         Basically, when creating a new Claim, what really is created first is the Main Snak. So,
 *         this requires a field 'mainSnak' which can have all fields which can be defined in
 *         jQuery.snakview's option 'predefined'. E.g. "predefined.mainSnak.property = 'q42'"
 *         TODO: also allow pre-defining aspects of qualifiers. Implementation and whether this
 *               makes sense here might depend on whether we will have one or several edit buttons.
 *
 * @option locked {Object} Elements that shall be locked (disabled).
 *
 * @event startediting: Triggered when starting the Claim's edit mode.
 *        (1) {jQuery.Event}
 *
 * @event afterstartediting: Triggered after having started the Claim's edit mode.
 *        (1) {jQuery.Event}
 *
 * @event stopediting: Triggered when stopping the Claim's edit mode.
 *        (1) {jQuery.Event}
 *        (2) {boolean} If true, the value from before edit mode has been started will be reinstated
 *            (basically a cancel/save switch).
 *
 * @event afterstopediting: Triggered after having stopped the Claim's edit mode.
 *        (1) {jQuery.Event}
 *
 * @event remove: Triggered when removing the claim.
 *        (1) {jQuery.Event} event
 *
 * @event toggleerror: Triggered when an error occurred or is resolved.
 *        (1) {jQuery.Event} event
 *        (2) {boolean} Set to true if the error has just occurred, false if resolved
 */
$.widget( 'wikibase.claimview', PARENT, {
	widgetName: 'wikibase-claimview',
	widgetBaseClass: 'wb-claimview',

	/**
	 * (Additional) default options
	 * @see jQuery.Widget.options
	 */
	options: {
		template: 'wb-claim',
		templateParams: [
			'wb-last', // class: wb-first|wb-last
			function() { // class='wb-claim-$2'
				return ( this._claim && this._claim.getGuid() ) || 'new';
			},
			'', // .wb-claim-mainsnak
			''  // edit section DOM
		],
		templateShortCuts: {
			'$mainSnak': '.wb-claim-mainsnak',
			'$toolbar': '.wb-claim-toolbar'
		},
		value: null,
		predefined: {
			mainSnak: false
		},
		locked: {
			mainSnak: false
		}
	},

	/**
	 * The node representing the main snak, displaying it in a jQuery.snakview
	 * @type jQuery
	 */
	$mainSnak: null,

	/**
	 * Node of the toolbar
	 * @type jQuery
	 */
	$toolbar: null,

	/**
	 * The anchor object of an error tooltip when one is set.
	 * @type Object
	 */
	errorTooltipAnchor: null,

	/**
	 * The claim represented by this view or null if this is a view for a user to enter a new claim.
	 * @type wb.Claim|null
	 */
	_claim: null,

	/**
	 * Whether the Claim is currently in edit mode.
	 * @type {boolean}
	 */
	_isInEditMode: false,

	/**
	 * Whether saving is (currently) disabled.
	 * @type {boolean}
	 */
	_saveDisabled: false,

	/**
	 * @see jQuery.Widget._create
	 */
	_create: function() {
		var self = this;
		this._claim = this.option( 'value' );

		// call template creation, this will require this._claim in template params callback!
		PARENT.prototype._create.call( this );

		// set up event listeners:
		this.$mainSnak
		.on ( 'snakviewchange', function( event, status ) {
			var snakview = self.$mainSnak.data( 'snakview' );
			( !snakview.isValid() || snakview.isInitialSnak() )
				? self._disableSave()
				: self._enableSave();
		} )
		.on( 'snakviewstopediting', function( event, dropValue ) {
			// React on key stroke events (e.g. pressing enter or ESC key)
			if ( self.isSaveDisabled() && !dropValue ) {
				event.preventDefault();
				return;
			}

			if ( !self.__continueStopEditing ) {
				// Do not exit snakview's edit mode yet; Let any API request be performed first.
				event.preventDefault();
				self.stopEditing( dropValue );
			} else {
				self.__continueStopEditing = false;
			}
		} )
		.on( 'valueviewchange', function( e ) {
			if ( self.errorTooltipAnchor ) {
				self.errorTooltipAnchor.getTooltip().hide();
			}
		} );

		this.$mainSnak.snakview( {
			value: this.mainSnak() || {},
			locked: this.option( 'locked' ).mainSnak,
			autoStartEditing: false // manually, after toolbar is there, so events can access toolbar
		} );

		// toolbar for edit group:
		this._createToolbar();

		if( !this._claim ) {
			this.startEditing();
		}

	},

	/**
	 * Inserts the toolbar for editing the main snak of the claim.
	 * @since 0.3
	 *
	 * TODO: depending on how we will proceed with making whole claims editable (having edit forms
	 *       for main snak and qualifiers at once or having one form per snak), we will have to use
	 *       this code in a different place.
	 * TODO: would be nice to get rid of the whole toolbar code in here, moving it into a separate
	 *       widget which can be used for interaction with editable widgets like the Snakview.
	 */
	_createToolbar: function() {
		var self = this,
			toolbar = new wb.ui.Toolbar(),
			snakview = this.$mainSnak.data( 'snakview' );

		// give the toolbar an edit group with basic edit commands:
		toolbar.editGroup = new wb.ui.Toolbar.EditGroup( {
			displayRemoveButton: this._claim !== null // no remove button if not yet created
		} );
		toolbar.addElement( toolbar.editGroup );

		toolbar.editGroup.on( 'edit', function( e ) {
			self.startEditing();
			e.preventDefault(); // don't auto-transform toolbar
		} );

		toolbar.editGroup.on( 'cancel', function( e ) {
			self.cancelEditing();
			e.preventDefault(); // don't auto-transform toolbar
		} );

		toolbar.editGroup.on( 'save', function( e ) {
			self.stopEditing();
			e.preventDefault(); // don't auto-transform toolbar
		} );

		toolbar.editGroup.on( 'remove', function( e ) {
			self.disable();

			self.toggleActionMessage( { message: 'wikibase-remove-inprogress' }, function() {
				self._removeClaimApiCall()
				.always( function() {
					// Enable toolbar buttons before toggling the toolbar state since the buttons
					// would be disabled when toggling the toolbar again.
					self.enable();
				} ).done( function( savedClaim, pageInfo ) {
					// NOTE: we don't update rev store here! If we want uniqueness for Claims, this
					//  might be an issue at a later point and we would need a solution then

					// update model of represented Claim
					self._trigger( 'remove' );
					// TODO: not really nice because remove handling doesn't make much sense if a
					//       $.claimview would be used on its own
				} ).fail( function( errorCode, details ) {
					var btnRemove = self.$toolbar.find( '.wb-ui-toolbar' ).data( 'wb-toolbar' )
						.editGroup.btnRemove,
					error = wb.RepoApiError.newFromApiResponse( errorCode, details, 'remove' );

					self.toggleActionMessage( function() {
						self.displayError( error, btnRemove );
					} );
				} );
			} );
		} );

		if ( this._claim || this.options.predefined.mainSnak ) {
			var propertyName;
			if ( this._claim ) {
				propertyName = wb.entities[this.mainSnak().getPropertyId()].label;
			} else {
				propertyName = wb.entities[this.options.predefined.mainSnak.property].label;
			}
			toolbar.editGroup.setTooltip( mw.msg( 'wikibase-claimview-snak-tooltip', propertyName ) );
		} else {
			toolbar.editGroup.setTooltip( mw.msg( 'wikibase-claimview-snak-new-tooltip' ) );
		}

		// TODO: get rid of the editsection node!
		toolbar.appendTo( $( '<span/>' ).addClass( 'wb-editsection' ).appendTo( this.$toolbar ) );
	},

	/**
	 * Starts the Claim's edit mode.
	 * @since 0.4
	 *
	 * @return {undefined} (allows chaining widget calls)
	 */
	startEditing: $.NativeEventHandler( 'startEditing', {
		// don't start edit mode or trigger event if in edit mode already:
		initially: function( e ) {
			if( this.isInEditMode() ) {
				e.cancel();
			}
		},
		// start edit mode if event doesn't prevent default:
		natively: function( e ) {
			var snakview = this.$mainSnak.data( 'snakview' );

			this.$mainSnak.data( 'snakview' ).startEditing();

			this.element.addClass( 'wb-edit' );
			this._isInEditMode = true;

			this.$toolbar.find( '.wb-ui-toolbar' ).data( 'wb-toolbar' ).editGroup.toEditMode();
			if ( !snakview.isValid() || snakview.isInitialSnak() ) {
				this._disableSave();
			}

			this._trigger( 'afterstartediting' );
		}
	} ),

	/**
	 * Exits the Claim's edit mode.
	 * @since 0.4
	 *
	 * @param {boolean} [dropValue] If true, the value from before edit mode has been started will
	 *        be reinstated - basically a cancel/save switch. "false" by default. Consider using
	 *        cancelEditing() instead.
	 * @return {undefined} (allows chaining widget calls)
	 */
	stopEditing: $.NativeEventHandler( 'stopEditing', {
		// don't stop edit mode or trigger event if not in edit mode currently:
		initially: function( e, dropValue ) {
			if( !this.isInEditMode() || this._saveDisabled && !dropValue ) {
				e.cancel();
			}

			this.__continueStopEditing = true;

			if ( self.errorTooltipAnchor ) {
				self.errorTooltipAnchor.getTooltip().hide();
			}
			this.element.removeClass( 'wb-error' );
		},
		// start edit mode if custom event handlers didn't prevent default:
		natively: function( e, dropValue ) {
			var self = this,
				toolbar = this.$toolbar.find( '.wb-ui-toolbar' ).data( 'wb-toolbar' );

			this.disable();

			if ( dropValue ) {
				// nothing to update
				if ( this.$mainSnak.data( 'snakview' ) ) {
					this.$mainSnak.data( 'snakview' ).stopEditing( dropValue );
				}

				this.toggleActionMessage( { duration: 0 }, function() {
					self.enable();
					if ( toolbar ) { // toolbar might be removed from the DOM
						toolbar.editGroup.toNonEditMode();
						toolbar.editGroup.btnEdit.setFocus();
					}
					self.element.removeClass( 'wb-edit' );
					self._isInEditMode = false;
					self._trigger( 'afterstopediting', null, [ dropValue ] );
				} );
			} else if ( this._claim !== null ) {
				this.toggleActionMessage( { message: 'wikibase-save-inprogress' }, function() {
					// editing an existing claim
					if ( self._claim ) {
						self._saveMainSnakApiCall( self.$mainSnak.data( 'snakview' ).snak() )
						.done( function( savedClaim, pageInfo ) {
							self.$mainSnak.data( 'snakview' ).stopEditing( dropValue );

							// Enable before toggling the toolbar since just the state of the active
							// elements is changed (which would be the "edit" button only then).
							self.enable();

							toolbar.editGroup.toNonEditMode();

							self.toggleActionMessage( { duration: 0 }, function() {
								// TODO: When adding a claim, the focus is re-set on the
								// corresponding add button. This might change in the future
								// depending on how the interaction flow for adding a new claim will
								// be implemented. For the moment, do not focus the edit button when
								// a new value is being added since the focus will be re-set to the
								// "add" button after the API call adding the new claim has
								// finished.
								if ( !$( e.target ).parent().hasClass( 'wb-claim-new' ) ) {
									toolbar.editGroup.btnEdit.setFocus();
								}

								if ( !self._claim ) {
									// claim must be newly entered, create a new claim:
									self._claim = new wb.Claim(
										self.$mainSnak.data( 'snakview' ).value()
									);
								}

								self.element.removeClass( 'wb-edit' );
								self._isInEditMode = false;

								// transform toolbar and snak view after save complete
								self._trigger( 'afterstopediting', null, [ dropValue ] );
							} );
						} )
						.fail( function( errorCode, details ) {
							var error = wb.RepoApiError.newFromApiResponse(
									errorCode, details, 'save'
								);

							self.toggleActionMessage( function() {
								self.displayError( error, toolbar.editGroup.btnSave );
								self.enable();
							} );

							self.__continueStopEditing = false;
						} );
					}
				} );
			} else {
				this._isInEditMode = false;
				this._trigger( 'afterstopediting', null, [ dropValue ] );
			}
		}
	} ),

	/**
	 * Short-cut for stopEditing( false ). Exits edit mode and restores the value from before the
	 * edit mode has been started.
	 * @since 0.4
	 *
	 * @return {undefined} (allows chaining widget calls)
	 */
	cancelEditing: function() {
		return this.stopEditing( true ); // stop editing and drop value
	},

	/**
	 * Returns whether the Claim is editable at the moment.
	 * @since 0.4
	 *
	 * @return {boolean}
	 */
	isInEditMode: function() {
		return this._isInEditMode;
	},

	/**
	 * Toggles the message displayed instead of the toolbar while performing an (API) action
	 * (saving/removing). Omit action parameter to show the toolbar.
	 * @since 0.4
	 *
	 * @param {Object|Function} [options] String that is used to get the message key. Assumed to be
	 *        a callback if of type function. You may set the following options:
	 *        message:  The message key of the message to display. If not defined, the toolbar will
	 *                  be shown.
	 *        duration: Fade duration in milliseconds (default: 200).
	 * @param {Function} [callback] Function to be called after toggling has been finished
	 */
	toggleActionMessage: function( options, callback ) {
		var $toolbar = this.$toolbar,
			actionMessageClass = this.widgetBaseClass + '-actionmsg';

		if ( options === undefined || $.isFunction( options ) ) {
			if ( $.isFunction( options ) ) {
				callback = options;
			}
			options = {};
		}

		if ( !options.duration ) {
			options.duration = 200; // default fade duration
		}

		if ( !options.message ) { // show toolbar
			if ( !$toolbar.find( '.' + actionMessageClass ).length ) {
				// If no action message is displayed currently, just trigger the callback.
				if ( $.isFunction( callback ) ) {
					callback();
				}
			} else {
				$toolbar.find( '.' + actionMessageClass ).fadeOut( options.duration, function() {
					$toolbar.find( '.' + actionMessageClass ).remove();
					$toolbar.find( '.wb-ui-toolbar' ).fadeIn( options.duration, function() {
						if ( $.isFunction( callback ) ) {
							callback();
						}
					} );
				} );
			}
		} else { // show message
			var actionMsg = $( '<span/>' )
			.addClass( actionMessageClass + ' wb-actionmsg' )
			.append( $( '<span/>' ).text( mw.msg( options.message ) ) );

			actionMsg.appendTo( this.$toolbar.find( '.wb-editsection' ) );

			$toolbar.find( '.wb-ui-toolbar' ).hide();

			actionMsg.fadeIn( options.duration, function() {
				if ( $.isFunction( callback ) ) {
					callback();
				}
			} );
		}
	},

	/**
	 * Triggers the API call to save the Main Snak.
	 * @since 0.4
	 *
	 * TODO: would be nice to have all API related stuff out of here to allow concentrating on
	 *       MVVM relation.
	 *
	 * @param {wb.Snak} mainSnak
	 * @return jQuery.Promise
	 */
	_saveMainSnakApiCall: function( mainSnak ) {
		if( !this.value() ) {
			throw new Error( 'Can\'t save Main Snak of non-existent Claim' );
		}
		// store changed value of Claim's Main Snak:
		var self = this,
			guid = this.value().getGuid(),
			api = new wb.RepoApi(),
			revStore = wb.getRevisionStore();

		return api.setClaimValue(
			guid,
			revStore.getClaimRevision( guid ),
			mainSnak
		).done( function( savedClaim, pageInfo ) {
			// update revision store
			revStore.setClaimRevision( pageInfo.lastrevid, savedClaim.getGuid() );

			// update model of represented Claim
			self._claim.setMainSnak( savedClaim.getMainSnak() );
		} );
	},

	/**
	 * Triggers the API call to remove the Claim.
	 * @since 0.4
	 *
	 * TODO: same as for _saveMainSnakApiCall(), get API related stuff out of here!
	 *
	 * @return jQuery.Promise
	 */
	_removeClaimApiCall: function() {
		var guid = this.value().getGuid(),
			api = new wb.RepoApi(),
			revStore = wb.getRevisionStore();

		return api.removeClaim(
			guid,
			revStore.getClaimRevision( guid )
		);
	},

	/**
	 * Disables the Claim view.
	 * @since 0.4
	 */
	disable: function() {
		this.$toolbar.find( '.wb-ui-toolbar' ).data( 'wb-toolbar' ).disable();
		this.$mainSnak.data( 'snakview' ).disable();
	},

	/**
	 * Enables the Claim view.
	 * @since 0.4
	 */
	enable: function() {
		this.$toolbar.find( '.wb-ui-toolbar' ).data( 'wb-toolbar' ).enable();
		this.$mainSnak.data( 'snakview' ).enable();
	},

	/**
	 * Disables "save" functionality.
	 * @since 0.4
	 */
	_disableSave: function() {
		this._saveDisabled = true;
		if ( this.$toolbar.find( '.wb-ui-toolbar' ).data( 'wb-toolbar' ) ) {
			this.$toolbar.find( '.wb-ui-toolbar' ).data( 'wb-toolbar' ).editGroup.btnSave.disable();
		}
	},

	/**
	 * Enables "save" functionality.
	 * @since 0.4
	 */
	_enableSave: function() {
		this._saveDisabled = false;
		this.$toolbar.find( '.wb-ui-toolbar' ).data( 'wb-toolbar' ).editGroup.btnSave.enable();
	},

	/**
	 * Returns whether saving is currently disabled.
	 * @since 0.4
	 *
	 * @return {boolean}
	 */
	isSaveDisabled: function() {
		return this._saveDisabled;
	},

	/**
	 * Displays an error message an visualizes the error state.
	 * @since 0.4
	 *
	 * @param {wb.RepoApiError} error
	 * @param {Object} anchor Object which the tooltip shall be attached to.
	 */
	displayError: function( error, anchor ) {
		var self = this;

		anchor.setTooltip( error ).show( true );

		this.element.addClass( 'wb-error' );

		anchor.getTooltip().on( 'hide', function( e ) {
			self.element.removeClass( 'wb-error' ).addClass( 'wb-edit' );
			self._trigger( 'toggleerror', null, [ false ] );
		} );

		anchor.getTooltip().on( 'afterhide', function( e ) {
			anchor.removeTooltip();
			self.errorTooltipAnchor = null;
		} );

		this.errorTooltipAnchor = anchor;

		this._trigger( 'toggleerror', null, [ true ] );
	},

	/**
	 * @see $.widget.destroy
	 */
	destroy: function() {
		this.element.removeClass( this.widgetBaseClass );

		this.$mainSnak.snakview( 'destroy' );

		$.Widget.prototype.destroy.call( this );
	},

	/**
	 * Returns the current Claim represented by the view. If null is returned, than this is a
	 * fresh view where a new Claim is being constructed.
	 * @since 0.3
	 *
	 * @return wb.Claim|null
	 */
	value: function() {
		return this._claim;
	},

	/**
	 * Returns the current Claim's main snak or null if no Claim is represented by the view
	 * currently (because Claim not yet constructed). This is a short cut to value().getMainSnak().
	 *
	 * NOTE: this function has been introduced for the big referenceview hack, where we let the
	 *  referenceview widget inherit from the claimview until qualifiers will be implemented - and
	 *  therefore a more generic base widget which will serve as base for both - claimview and
	 *  referenceview.
	 *
	 * @deprecated Use .value() instead.
	 *
	 * @since 0.4
	 *
	 * @return wb.Snak|null
	 */
	mainSnak: function() {
		return this._claim
			? this._claim.getMainSnak()
			: ( this.option( 'predefined' ).mainSnak || null );
	},

	/**
	 * @see jQuery.widget._setOption
	 * We are using this to disallow changing the value option afterwards
	 */
	_setOption: function( key, value ) {
		if( key === 'value' ) {
			throw new Error( 'Can not set value after initialization' );
		}
		$.Widget.prototype._setOption.call( key, value );
	}
} );

}( mediaWiki, wikibase, jQuery ) );
