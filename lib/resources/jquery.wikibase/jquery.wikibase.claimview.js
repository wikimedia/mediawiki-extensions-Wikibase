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
 * @option helpMessage {string} End-user message explaining how to use the claimview widget. The
 *         message is most likely to be used inside the tooltip of the toolbar corresponding to
 *         the claimview.
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
 *        (2) {boolean} If true, the value from before edit mode has been started will be reinstated
 *            (basically a cancel/save switch).
 *
 * @event change: Triggered whenever the claimview's content is changed.
 *        (1) {jQuery.Event} event
 *
 * @event toggleerror: Triggered when an error occurred or is resolved.
 *        (1) {jQuery.Event} event
 *        (2) {wb.RepoApiError|undefined} wb.RepoApiError object if an error occurred, undefined if
 *            the current error state is resolved.
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
			function() { // class='wb-claim-$2'
				return ( this._claim && this._claim.getGuid() ) || 'new';
			},
			'wb-last', // class: wb-first|wb-last
			'', // .wb-claim-mainsnak
			'' // Qualifiers
		],
		templateShortCuts: {
			'$mainSnak': '.wb-claim-mainsnak',
			'$qualifiers': '.wb-statement-qualifiers'
		},
		value: null,
		predefined: {
			mainSnak: false
		},
		locked: {
			mainSnak: false
		},
		helpMessage: mw.msg( 'wikibase-claimview-snak-new-tooltip' )
	},

	/**
	 * The node representing the main snak, displaying it in a jQuery.snakview
	 * @type jQuery
	 */
	$mainSnak: null,

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
	 * Reference to the listview widget managing the qualifier snaks. Basically, just a short-cut
	 * for this.$qualifiers.data( 'listview' )
	 * @type {jquery.wikibase.listview}
	 */
	_qualifiers: null,

	/**
	 * Whether the Claim is currently in edit mode.
	 * @type {boolean}
	 */
	_isInEditMode: false,

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
			self._trigger( 'change' );
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

		// Initialize qualifiers:
		// TODO: Allow adding qualifiers when adding a new claim.
		if ( this.option( 'value' ) ) {
			var $qualifiers = $( '<div/>' )
			.prependTo( this.$qualifiers )
			.snaklistview( {
				value: ( this._claim ) ? this._claim.getQualifiers() : null
			} )
			.on( 'snaklistviewchange', function( event ) {
				self._trigger( 'change' );
			} );

			this._qualifiers = $qualifiers.data( 'snaklistview' );
		}

		this._attachEditModeEventHandlers();

		if ( this._claim || this.options.predefined.mainSnak ) {
			var property = this._claim
				? this.mainSnak().getPropertyId()
				: this.options.predefined.mainSnak.property;

			var fetchedProperty = wb.fetchedEntities[ property ];
			if( fetchedProperty ) {
				this.options.helpMessage = mw.msg(
					'wikibase-claimview-snak-tooltip', fetchedProperty.getContent().getLabel() );
			}
		}
	},

	/**
	 * Returns whether the claimview is valid according to its current contents. An Empty value
	 * will be considered not valid (also, an empty value can not be saved).
	 * @since 0.4
	 *
	 * @return {boolean}
	 */
	isValid: function() {
		if( this._qualifiers && !this._qualifiers.isValid() ) {
			return false;
		}

		try {
			this._instantiateClaim( null );
		} catch( e ) {
			return false;
		}
		return true;
	},

	/**
	 * Returns whether the current value of this claim (including the qualifiers) equals the value
	 * the claim has been initialized with.
	 * @since 0.4
	 *
	 * @returns {boolean}
	 */
	isInitialValue: function() {
		return  this.$mainSnak.data( 'snakview' ).isInitialSnak()
			&& ( !this._qualifiers || this._qualifiers.isInitialValue() );
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

			if ( this._qualifiers ) {
				this._qualifiers.startEditing();
			}

			this.element.addClass( 'wb-edit' );
			this._isInEditMode = true;

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
			if (
				!this.isInEditMode() || ( !this.isValid() || this.isInitialValue() ) && !dropValue
			) {
				e.cancel();
			}

			this.element.removeClass( 'wb-error' );
		},
		// start edit mode if custom event handlers didn't prevent default:
		natively: function( e, dropValue ) {
			var self = this;

			this._detachEditModeEventHandlers();

			this.disable();

			if ( dropValue ) {
				if ( this.$mainSnak.data( 'snakview' ) ) {
					this.$mainSnak.data( 'snakview' ).stopEditing( dropValue );
				}

				if ( this._qualifiers ) {
					this._qualifiers.stopEditing( dropValue );
				}

				self.enable();
				self.element.removeClass( 'wb-edit' );
				self._isInEditMode = false;

				self._attachEditModeEventHandlers();

				self._trigger( 'afterstopediting', null, [ dropValue ] );
			} else {
				// editing an existing claim
				self._saveClaimApiCall()
				.done( function( savedClaim, pageInfo ) {
					self.$mainSnak.data( 'snakview' ).stopEditing( dropValue );

					if ( self._qualifiers ) {
						self._qualifiers.stopEditing();
					}

					self.enable();

					if ( !self._claim ) {
						// claim must be newly entered, create a new claim:
						self._claim = new wb.Claim(
							self.$mainSnak.data( 'snakview' ).value()
						);
					}

					self.element.removeClass( 'wb-edit' );
					self._isInEditMode = false;

					self._attachEditModeEventHandlers();

					// transform toolbar and snak view after save complete
					self._trigger( 'afterstopediting', null, [ dropValue ] );
				} )
				.fail( function( errorCode, details ) {
					var error = wb.RepoApiError.newFromApiResponse(
							errorCode, details, 'save'
						);

					self.enable();

					self._attachEditModeEventHandlers();

					self.setError( error );
				} );
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
	 * Attaches event listeners that shall trigger stopping the claimview's edit mode.
	 * @since 0.4
	 */
	_attachEditModeEventHandlers: function() {
		var self = this;

		function defaultHandling( event, dropValue ) {
			event.stopImmediatePropagation();
			event.preventDefault();
			self._detachEditModeEventHandlers();
			self._attachEditModeEventHandlers();
			self.stopEditing( dropValue );
		}

		this.$mainSnak.one( 'snakviewstopediting', function( event, dropValue ) {
			defaultHandling( event, dropValue );
		} );

		if ( this._qualifiers ) {
			this._qualifiers.element.one( 'snaklistviewstopediting', function( event, dropValue ) {
				defaultHandling( event, dropValue );
			} );
		}
	},

	/**
	 * Detaches event listeners that shall trigger stopping the claimview's edit mode.
	 * @since 0.4
	 */
	_detachEditModeEventHandlers: function() {
		this.$mainSnak.off( 'snakviewstopediting' );

		if ( this._qualifiers ) {
			this._qualifiers.element.off( 'snaklistviewstopediting' );
		}
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
	 * Instantiates a claim with the claimview's current value.
	 * @since 0.4
	 *
	 * @param {string} guid
	 * @return {wb.Claim}
	 * @throws {Error} In case the widget's current value is insufficient for building a claim.
	 */
	_instantiateClaim: function( guid ) {
		return new wb.Claim(
			this.$mainSnak.data( 'snakview' ).snak(),
			( this._qualifiers ) ? this._qualifiers.value() : null,
			guid
		);
	},

	/**
	 * Triggers the API call to save the claim.
	 * @since 0.4
	 *
	 * TODO: would be nice to have all API related stuff out of here to allow concentrating on
	 *       MVVM relation.
	 *
	 * @return {jQuery.Promise}
	 */
	_saveClaimApiCall: function() {
		var self = this,
			abstractedApi = new wb.AbstractedRepoApi(),
			revStore = wb.getRevisionStore(),
			guid;

		if ( this.value() ) {
			guid = this.value().getGuid();
		} else {
			var guidGenerator = new wb.utilities.ClaimGuidGenerator();
			guid = guidGenerator.newGuid( mw.config.get( 'wbEntityId' ) );
		}

		return abstractedApi.setClaim( this._instantiateClaim( guid ), revStore.getClaimRevision( guid ) )
		.done( function( savedClaim, pageInfo ) {
			// Update revision store:
			revStore.setClaimRevision( pageInfo.lastrevid, savedClaim.getGuid() );

			// Update model of represented Claim:
			self._claim = savedClaim;

			// If the claim was pending (adding a new claim instead of editing an existing one),
			// there are no qualifiers set yet.
			if ( self._qualifiers ) {
				self._qualifiers.value( savedClaim.getQualifiers() );
			}
		} );
	},

	/**
	 * Sets/removes error state from the widget.
	 * @since 0.4
	 *
	 * @param {wb.RepoApiError} [error]
	 */
	setError: function( error ) {
		if ( error ) {
			this.element.addClass( 'wb-error' );
			this._trigger( 'toggleerror', null, [ error ] );
		} else {
			this.element.removeClass( 'wb-error' );
			this._trigger( 'toggleerror' );
		}
	},

	/**
	 * Disables the Claim view.
	 * @since 0.4
	 */
	disable: function() {
		this.$mainSnak.data( 'snakview' ).disable();
		if ( this._qualifiers ) {
			this._qualifiers.disable();
		}
	},

	/**
	 * Enables the Claim view.
	 * @since 0.4
	 */
	enable: function() {
		this.$mainSnak.data( 'snakview' ).enable();
		if ( this._qualifiers ) {
			this._qualifiers.enable();
		}
	},

	/**
	 * @see $.widget.destroy
	 */
	destroy: function() {
		this.$mainSnak.snakview( 'destroy' );
		PARENT.prototype.destroy.call( this );
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

// Register toolbars:
$.wikibase.toolbarcontroller.definition( 'addtoolbar', {
	id: 'claim-qualifiers-snak',
	selector: '.wb-claim-qualifiers',
	events: {
		snaklistviewstartediting: 'create',
		snaklistviewafterstopediting: 'destroy',
		snaklistviewchange: function( event ) {
			var snaklistview = $( event.target ).data( 'snaklistview' ),
				addToolbar = $( event.target ).data( 'addtoolbar' );
			if ( addToolbar ) {
				addToolbar.toolbar[snaklistview.isValid() ? 'enable' : 'disable']();
			}
		},
		snaklistviewdisable: function( event ) {
			var addToolbar = $( event.target ).data( 'addtoolbar' );
			// Toolbar might be removed from the DOM already after having stopped edit mode.
			if ( addToolbar ) {
				addToolbar.toolbar.disable();
			}
		},
		snaklistviewenable: function( event ) {
			var addToolbar = $( event.target ).data( 'addtoolbar' );
			// "add" toolbar might be remove already.
			if ( addToolbar ) {
				addToolbar.toolbar.enable();
			}
		},
		'listviewitemadded listviewitemremoved': function( event ) {
			// Enable "add" link when all qualifiers have been removed:
			var $listviewNode = $( event.target ),
				listview = $listviewNode.data( 'listview' ),
				$snaklistviewNode = $listviewNode.closest( '.wb-snaklistview' ),
				snaklistview = $snaklistviewNode.data( 'snaklistview' ),
				addToolbar = $snaklistviewNode.data( 'addtoolbar' );

			// Toolbar is not within the DOM when (re-)constructing the list in non-edit-mode.
			if ( !addToolbar ) {
				return;
			}

			// Disable "add" toolbar when the last qualifier has been removed:
			if ( !snaklistview.isValid() && listview.items().length ) {
				addToolbar.toolbar.disable();
			} else {
				addToolbar.toolbar.enable();
			}
		}
	},
	options: {
		customAction: function( event, $parent ) {
			$parent.data( 'snaklistview' ).enterNewItem();
		},
		eventPrefix: $.wikibase.snaklistview.prototype.widgetEventPrefix,
		addButtonLabel: mw.msg( 'wikibase-addqualifier' )
	}
} );

$.wikibase.toolbarcontroller.definition( 'removetoolbar', {
	id: 'claim-qualifiers-snak',
	selector: '.wb-claim-qualifiers',
	events: {
		'snakviewstartediting snakviewcreate listviewitemadded listviewitemremoved': function( event ) {
			var $target = $( event.target ),
				listview = $target.closest( '.wb-snaklistview' ).data( 'snaklistview' )._listview;

			if ( event.type.indexOf( 'snakview' ) !== -1 ) {
				// Create toolbar for each snakview widget:
				$target.removetoolbar( {
					action: function( event ) {
						listview.removeItem( $target );
					}
				} );
			}
		},
		snaklistviewafterstopediting: function( event ) {
			// Destroy the snakview toolbars:
			var $snaklistviewNode = $( event.target ),
				listview = $snaklistviewNode.data( 'snaklistview' )._listview,
				lia = listview.listItemAdapter();

			$.each( listview.items(), function( i, item ) {
				var snakview = lia.liInstance( $( item ) );
				if ( snakview.element.data( 'removetoolbar' ) ) {
					snakview.element.data( 'removetoolbar' ).destroy();
					snakview.element.children( '.w-removetoolbar' ).remove();
				}
			} );
		},
		'snaklistviewdisable snaklistviewenable': function( event ) {
			var $snaklistviewNode = $( event.target ),
				listview = $snaklistviewNode.data( 'snaklistview' )._listview,
				lia = listview.listItemAdapter(),
				action = ( event.type.indexOf( 'disable' ) !== -1 ) ? 'disable' : 'enable';

			$.each( listview.items(), function( i, item ) {
				var $item = $( item );
				// Item might be about to be removed not being a list item instance.
				if ( lia.liInstance( $item ) && $item.data( 'removetoolbar' ) ) {
					$item.data( 'removetoolbar' ).toolbar[action]();
				}
			} );
		}
	}
} );

}( mediaWiki, wikibase, jQuery ) );
