/**
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
 * @option {wb.datamodel.Claim|null} value The claim displayed by this view. This can only be set initially,
 *         the value function doesn't work as a setter in this view. If this is null, this view will
 *         start in edit mode, allowing the user to define the claim.
 *
 * @option {wb.store.EntityStore} entityStore
 *
 * @option {wikibase.ValueViewBuilder} valueViewBuilder
 *
 * @option {wikibase.entityChangers.EntityChangersFactory} entityChangersFactory
 *
 * @option {wikibase.entityChangers.ClaimsChanger} claimsChanger
 *
 * @option {number|null} index The claim's index within the list of claims (if the claim is
 *         contained within such a list).
 *         Default: null
 *         TODO: This option should be removed and a proper mechanism independent from claimview
 *         should be implemented to manage and store the indices of claims (bug #56050).
 *
 * @option {Object} predefined Allows to pre-define certain aspects of the Claim to be created.
 *         Basically, when creating a new Claim, what really is created first is the Main Snak. So,
 *         this requires a field 'mainSnak' which can have all fields which can be defined in
 *         jQuery.snakview's option 'predefined'. E.g. "predefined.mainSnak.property = 'q42'"
 *         TODO: also allow pre-defining aspects of qualifiers. Implementation and whether this
 *               makes sense here might depend on whether we will have one or several edit buttons.
 *
 * @option {Object} locked Elements that shall be locked (disabled).
 *
 * @option {string} helpMessage End-user message explaining how to use the claimview widget. The
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
	/**
	 * (Additional) default options
	 * @see jQuery.Widget.options
	 */
	options: {
		template: 'wb-claim',
		templateParams: [
			function() { // class='wb-claim-$1'
				return ( this._claim && this._claim.getGuid() ) || 'new';
			},
			function() {
				return $( '<div/>' );
			}, // .wb-claim-mainsnak
			'' // Qualifiers
		],
		templateShortCuts: {
			'$mainSnak': '.wb-claim-mainsnak > :first-child',
			'$qualifiers': '.wb-claim-qualifiers'
		},
		value: null,
		entityStore: null,
		valueViewBuilder: null,
		entityChangersFactory: null,
		claimsChanger: null,
		predefined: {
			mainSnak: false
		},
		locked: {
			mainSnak: false
		},
		index: null,
		helpMessage: mw.msg( 'wikibase-claimview-snak-new-tooltip' )
	},

	/**
	 * The node representing the main snak, displaying it in a jQuery.snakview
	 * @type jQuery
	 */
	$mainSnak: null,

	/**
	 * The claim represented by this view or null if this is a view for a user to enter a new claim.
	 * @type wb.datamodel.Claim|null
	 */
	_claim: null,

	/**
	 * Reference to the listview widget managing the qualifier snaklistviews. Basically, just a
	 * short-cut for this.$qualifiers.data( 'listview' )
	 * @type {$.wikibase.listview}
	 */
	_qualifiers: null,

	/**
	 * Caches the snak list of the qualifiers the claimview has been initialized with. The
	 * qualifiers are split into groups featuring the same property. Removing one of those groups
	 * results in losing the reference to those qualifiers. Therefore, _initialQualifiers is used
	 * to rebuild the list of qualifiers when cancelling and is used to query whether the qualifiers
	 * represent the initial state.
	 * @type {wb.datamodel.SnakList}
	 */
	_initialQualifiers: null,

	/**
	 * Whether the Claim is currently in edit mode.
	 * @type {boolean}
	 */
	_isInEditMode: false,

	/**
	* The claim's initial index within the list of claims (if it is contained within a list of
	* claims). The initial index is stored to be able to detect whether the index has changed and
	* the claim does not feature its initial value.
	* @type {number|null}
	*/
	_initialIndex: null,

	/**
	 * @see jQuery.Widget._create
	 *
	 * @throws {Error} if any required option is not specified.
	 */
	_create: function() {
		if( !this.options.entityStore || !this.options.valueViewBuilder || !this.options.entityChangersFactory ) {
			throw new Error( 'Required option(s) missing' );
		}

		var self = this;
		this._claim = this.option( 'value' );

		// call template creation, this will require this._claim in template params callback!
		PARENT.prototype._create.call( this );

		// Make sure sub-classes have this class, too
		this.element.addClass( 'wb-claimview' );

		// set up event listeners:
		this.$mainSnak
		.on ( 'snakviewchange', function( event, status ) {
			self._trigger( 'change' );
		} );

		this.$mainSnak.snakview( {
			value: this.mainSnak() || {},
			locked: this.option( 'locked' ).mainSnak,
			autoStartEditing: false, // manually, after toolbar is there, so events can access toolbar
			entityStore: this.options.entityStore,
			valueViewBuilder: this.option( 'valueViewBuilder' )
		} );

		this._initialIndex = this.option( 'index' );

		// Initialize qualifiers:
		this._initialQualifiers = ( this._claim ) ? this._claim.getQualifiers() : new wb.datamodel.SnakList();

		if( this._claim && this._initialQualifiers.length ) { // TODO: Allow adding qualifiers when adding a new claim.
			// Group qualifiers by property id:
			this._createQualifiersListview( this._initialQualifiers );
		}

		this._attachEditModeEventHandlers();

		if ( this._claim || this.options.predefined.mainSnak ) {
			var property = this._claim
				? this.mainSnak().getPropertyId()
				: this.options.predefined.mainSnak.property;

			var deferred = $.Deferred();
			var helpMessage = this.options.helpMessage;
			this.options.helpMessage = deferred.promise();

			if( property ) {
				this.options.entityStore.get( property ).done( function( fetchedProperty ) {
					if( fetchedProperty ) {
						helpMessage = mw.msg(
							'wikibase-claimview-snak-tooltip',
							wb.utilities.ui.buildPrettyEntityLabelText( fetchedProperty.getContent() )
						);
					}
					deferred.resolve( helpMessage );
				} );
			} else {
				deferred.resolve( helpMessage );
			}
		}
	},

	/**
	 * Creates the listview widget containing the qualifier snaklistview widgets. Omitting
	 * qualifiers parameter generates an empty list widget.
	 * @since 0.4
	 *
	 * @param {wb.datamodel.SnakList} [qualifiers]
	 */
	_createQualifiersListview: function( qualifiers ) {
		var self = this,
			groupedQualifierSnaks = null;

		// Group qualifiers by property id:
		if( qualifiers && qualifiers.length ) {
			var propertyIds = qualifiers.getPropertyOrder();

			groupedQualifierSnaks = [];

			for( var i = 0; i < propertyIds.length; i++ ) {
				groupedQualifierSnaks.push( qualifiers.getFilteredSnakList( propertyIds[i] ) );
			}
		}

		// Using the property id, qualifier snaks are split into groups of snaklistviews. These
		// snaklistviews are managed in a listview:
		var $qualifiers = this.$qualifiers.children();
		if( !$qualifiers.length ) {
			$qualifiers = $( '<div/>' ).prependTo( this.$qualifiers );
		}
		$qualifiers.listview( {
				listItemAdapter: new $.wikibase.listview.ListItemAdapter( {
					listItemWidget: $.wikibase.snaklistview,
					newItemOptionsFn: function( value ) {
						return {
							value: value || null,
							singleProperty: true,
							entityStore: self.option( 'entityStore' ),
							valueViewBuilder: self.option( 'valueViewBuilder' )
						};
					}
				} ),
				value: groupedQualifierSnaks
			} )
			.on( 'snaklistviewchange.' + this.widgetName
				+ ' listviewafteritemmove.' + this.widgetName,
				function( event ) {
					self._trigger( 'change' );
				}
			)
			.on( 'listviewitemremoved.' + this.widgetName, function( event, value, $itemNode ) {
				if( event.target === self._qualifiers.element.get( 0 ) ) {
					self._trigger( 'change' );
					return;
				}

				// Check if last snaklistview of a qualifier listview item has been removed and
				// remove the listview item if so:
				var $snaklistview = $( event.target ).closest( ':wikibase-snaklistview' ),
					snaklistview = $snaklistview.data( 'snaklistview' );

				if( !snaklistview.value() ) {
					self._qualifiers.removeItem( snaklistview.element );
				}
			} );

		this._qualifiers = $qualifiers.data( 'listview' );

		this._attachEditModeEventHandlers();
	},

	/**
	 * Destroys the listview widget containing the qualifier snaklistview widgets.
	 */
	_destroyQualifiersListView: function() {
		if( this._qualifiers ) {
			this._qualifiers.destroy();
			this.$qualifiers.empty();
			this._qualifiers = null;
		}
	},

	/**
	* @see jQuery.Widget.option
	*
	* @triggers change
	*/
	option: function( key, value ) {
		if( value === this.options[key] ) {
			return this;
		}

		var self = PARENT.prototype.option.apply( this, arguments );

		if( key === 'index' && value !== undefined ) {
			this._trigger( 'change' );
		}

		return self;
	},

	/**
	 * Returns the claim's initial index within the list of claims (if in any).
	 * @since 0.5
	 *
	 * @return {number|null}
	 */
	getInitialIndex: function() {
		return this._initialIndex;
	},

	/**
	 * Returns whether the claimview is valid according to its current contents. An empty value
	 * will be considered not valid (also, an empty value can not be saved).
	 * @since 0.4
	 *
	 * @return {boolean}
	 */
	isValid: function() {
		// Validate qualifiers:
		if( this._qualifiers ) {
			var snaklistviews = this._qualifiers.value();

			if( snaklistviews.length ) {
				for( var i = 0; i < snaklistviews.length; i++ ) {
					if( !snaklistviews[i].isValid() ) {
						return false;
					}
				}
			}
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
	 * @return {boolean}
	 */
	isInitialValue: function() {
		if( this.option( 'index' ) !== this._initialIndex ) {
			return false;
		}

		if( this._claim ) {
			var snaklistviews = ( this._qualifiers ) ? this._qualifiers.value() : [],
				qualifiers = new wb.datamodel.SnakList();

			// Generate a SnakList object featuring all current qualifier snaks to be able to
			// compare it to the SnakList object the claimview has been initialized with:
			if( snaklistviews.length ) {
				for( var i = 0; i < snaklistviews.length; i++ ) {
					if( snaklistviews[i].value() ) {
						qualifiers.add( snaklistviews[i].value() );
					}
				}
			}

			if( !qualifiers.equals( this._initialQualifiers ) ) {
				return false;
			}
		}

		return this.$mainSnak.data( 'snakview' ).isInitialSnak();
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
			this.$mainSnak.data( 'snakview' ).startEditing();

			if( !this._qualifiers && this._claim ) {
				this._createQualifiersListview();
			}

			// Start edit mode of all qualifiers:
			if( this._qualifiers ) {
				var snaklistviews = this._qualifiers.value();
				if ( snaklistviews.length ) {
					for( var i = 0; i < snaklistviews.length; i++ ) {
						snaklistviews[i].startEditing();
					}
				}
				// If there are no snaklistviews, there is no way for the "add qualifier" toolbar
				// to be
				this._qualifiers.element.trigger( 'qualifiersstartediting' );
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

				this._stopEditingQualifiers( dropValue );

				self.enable();
				self.element.removeClass( 'wb-edit' );
				self._isInEditMode = false;

				self._attachEditModeEventHandlers();

				self._trigger( 'afterstopediting', null, [ dropValue ] );
			} else {
				// editing an existing claim
				self._saveClaimApiCall()
				.done( function( savedClaim ) {
					self.$mainSnak.data( 'snakview' ).stopEditing( dropValue );

					self._stopEditingQualifiers( dropValue );

					self.enable();

					if ( !self._claim ) {
						// claim must be newly entered, create a new claim:
						self._claim = new wb.datamodel.Claim(
							self.$mainSnak.data( 'snakview' ).value()
						);
					}

					self.element.removeClass( 'wb-edit' );
					self._isInEditMode = false;

					self._attachEditModeEventHandlers();

					// transform toolbar and snak view after save complete
					self._trigger( 'afterstopediting', null, [ dropValue ] );
				} )
				.fail( function( error ) {
					self.enable();

					self._attachEditModeEventHandlers();

					self.setError( error );
				} );
			}
		}
	} ),

	/**
	 * Stops editing of the qualifiers listview.
	 * @since 0.4
	 *
	 * @param {boolean} dropValue
	 */
	_stopEditingQualifiers: function( dropValue ) {
		var snaklistviews,
			i;

		if( this._qualifiers ) {
			snaklistviews = this._qualifiers.value();

			if( !dropValue ) {
				// When saving the qualifier snaks, reset the initial qualifiers to the new ones.
				this._initialQualifiers = new wb.datamodel.SnakList();
			}

			if( snaklistviews.length ) {
				for( i = 0; i < snaklistviews.length; i++ ) {
					snaklistviews[i].stopEditing( dropValue );

					if( dropValue && !snaklistviews[i].value() ) {
						// Remove snaklistview from qualifier listview if no snakviews are left in
						// that snaklistview:
						this._qualifiers.removeItem( snaklistviews[i].element );
					} else if ( !dropValue ) {
						// Gather all the current snaks in a single SnakList to set to reset the
						// initial qualifiers:
						this._initialQualifiers.add( snaklistviews[i].value() );
					}
				}
			}
		}

		// Destroy and (if qualifiers still exist) re-create the qualifier listview in order to
		// re-group the qualifiers by their property. This will also send out the event to erase
		// the "add qualifier" toolbar.
		this._destroyQualifiersListView();

		if( this._initialQualifiers.length > 0 ) {
			// Refill the qualifier listview with the initial (or new initial) qualifiers:
			this._createQualifiersListview( this._initialQualifiers );
		}
	},

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

		this._detachEditModeEventHandlers();

		function defaultHandling( event, dropValue ) {
			event.stopImmediatePropagation();
			event.preventDefault();
			self._detachEditModeEventHandlers();
			self._attachEditModeEventHandlers();
			self.stopEditing( dropValue );
		}

		this.$mainSnak.one( 'snakviewstopediting.' + this.widgetName, function( event, dropValue ) {
			defaultHandling( event, dropValue );
		} );

		if( this._qualifiers ) {
			this._qualifiers.element
			.one( 'snaklistviewstopediting.' + this.widgetName, function( event, dropValue ) {
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

		if ( this._qualifiers && this._qualifiers.value().length ) {
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
	 * @return {wb.datamodel.Claim}
	 * @throws {Error} In case the widget's current value is insufficient for building a claim.
	 */
	_instantiateClaim: function( guid ) {
		var qualifiers = new wb.datamodel.SnakList(),
			snaklistviews = this._qualifiers ? this._qualifiers.value() : [];

		// Combine qualifiers grouped by property to a single SnakList:
		for( var i = 0; i < snaklistviews.length; i++ ) {
			qualifiers.add( snaklistviews[i].value() );
		}

		return new wb.datamodel.Claim(
			this.$mainSnak.data( 'snakview' ).snak(),
			qualifiers,
			guid
		);
	},

	/**
	 * Triggers the API call to save the claim.
	 * @since 0.4
	 *
	 * @return {jQuery.Promise}
	 */
	_saveClaimApiCall: function() {
		var self = this,
			guid;

		if ( this.value() ) {
			guid = this.value().getGuid();
		} else {
			var guidGenerator = new wb.utilities.ClaimGuidGenerator();
			guid = guidGenerator.newGuid( mw.config.get( 'wbEntityId' ) );
		}

		return this.option( 'claimsChanger' ).setClaim(
			this._instantiateClaim( guid ),
			this.option( 'index' )
		)
		.done( function( savedClaim ) {
			// Update model of represented Claim:
			self._claim = savedClaim;
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
	 * @return wb.datamodel.Claim|null
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
	 * @return wb.datamodel.Snak|null
	 */
	mainSnak: function() {
		return this._claim
			? this._claim.getMainSnak()
			: ( this.option( 'predefined' ).mainSnak || null );
	},

	/**
	 * @see jQuery.Widget._setOption
	 */
	_setOption: function( key, value ) {
		if( key === 'value' ) {
			throw new Error( 'Can not set value after initialization' );
		}

		var response = PARENT.prototype._setOption.apply( this, arguments );

		if( key === 'disabled' ) {
			this.$mainSnak.data( 'snakview' ).option( key, value );
			if( this._qualifiers ) {
				this._qualifiers.option( key, value );
			}
		}

		return response;
	}
} );

// Register toolbars:
$.wikibase.toolbarcontroller.definition( 'addtoolbar', {
	id: 'claim-qualifiers-snak',
	selector: '.wb-claim-qualifiers',
	events: {
		'listviewcreate snaklistviewstartediting': function( event, toolbarController ) {
			var $target = $( event.target ),
				$qualifiers = $target.closest( '.wb-claim-qualifiers' ),
				listview = $target.closest( ':wikibase-listview' ).data( 'listview' );

			if(
				event.type === 'listviewcreate' && listview.items().length === 0
				|| event.type === 'snaklistviewstartediting'
			) {
				$qualifiers.addtoolbar( {
					addButtonAction: function() {
						listview.enterNewItem();
						listview.value()[listview.value().length - 1].enterNewItem();
					},
					addButtonLabel: mw.msg( 'wikibase-addqualifier' )
				} );

				toolbarController.registerEventHandler(
					event.data.toolbar.type,
					event.data.toolbar.id,
					'listviewdestroy snaklistviewafterstopediting',
					function( event, toolbarcontroller ) {
						var $target = $( event.target ),
							$qualifiers = $target.closest( '.wb-claim-qualifiers' );

						if( $target.parent().get( 0 ) !== $qualifiers.get( 0 ) ) {
							// Not the qualifiers main listview.
							return;
						}

						toolbarcontroller.destroyToolbar( $qualifiers.data( 'addtoolbar' ) );
					}
				);

				toolbarController.registerEventHandler(
					event.data.toolbar.type,
					event.data.toolbar.id,
					'snaklistviewchange',
					function( event ) {
						var $target = $( event.target ),
							$qualifiers = $target.closest( '.wb-claim-qualifiers' ),
							addToolbar = $qualifiers.data( 'addtoolbar' ),
							$listview = $target.closest( ':wikibase-listview' ),
							snaklistviews = $listview.data( 'listview' ).value();

						if( addToolbar ) {
							addToolbar.toolbar.enable();
							for( var i = 0; i < snaklistviews.length; i++ ) {
								if( !snaklistviews[i].isValid() ) {
									addToolbar.toolbar.disable();
									break;
								}
							}
						}
					}
				);

				toolbarController.registerEventHandler(
					event.data.toolbar.type,
					event.data.toolbar.id,
					// FIXME: When there are qualifiers, no state change events will be thrown.
					'listviewdisable',
					function( event ) {
						var $qualifiers = $( event.target ).closest( '.wb-claim-qualifiers' ),
							addToolbar = $qualifiers.data( 'addtoolbar' ),
							$parentView = $qualifiers.closest( ':wikibase-statementview' ),
							parentView = null;

						if( $parentView.length ) {
							parentView = $parentView.data( 'statementview' );
						} else {
							$parentView = $qualifiers.closest( ':wikibase-claimview' );
							parentView = $parentView.data( 'claimview' );
						}

						// Toolbar might be removed from the DOM already after having stopped edit mode.
						if( addToolbar ) {
							var toolbar = addToolbar.toolbar;
							toolbar[parentView.option( 'disabled' ) ? 'disable' : 'enable']();
						}
					}
				);

				toolbarController.registerEventHandler(
					event.data.toolbar.type,
					event.data.toolbar.id,
					'listviewitemadded listviewitemremoved',
					function( event ) {
						// Enable "add" link when all qualifiers have been removed:
						var $listviewNode = $( event.target ),
							listview = $listviewNode.data( 'listview' ),
							$snaklistviewNode = $listviewNode.closest( '.wb-snaklistview' ),
							snaklistview = $snaklistviewNode.data( 'snaklistview' ),
							addToolbar = $snaklistviewNode.data( 'addtoolbar' );

						// Toolbar is not within the DOM when (re-)constructing the list in non-edit-mode.
						if( !addToolbar ) {
							return;
						}

						// Disable "add" toolbar when the last qualifier has been removed:
						if( !snaklistview.isValid() && listview.items().length ) {
							addToolbar.toolbar.disable();
						} else {
							addToolbar.toolbar.enable();
						}
					}
				);

			}
		}
	}
} );

$.wikibase.toolbarcontroller.definition( 'removetoolbar', {
	id: 'claim-qualifiers-snak',
	selector: '.wb-claim-qualifiers',
	events: {
		'snakviewstartediting': function( event, toolbarController ) {
			var $target = $( event.target ),
				$snaklistview = $target.closest( '.wb-snaklistview' ),
				snaklistview = $snaklistview.data( 'snaklistview' );

			if( !snaklistview ) {
				return;
			}

			var qualifierPorpertyGroupListview = snaklistview._listview;

			// Create toolbar for each snakview widget:
			$target.removetoolbar( {
				action: function( event ) {
					qualifierPorpertyGroupListview.removeItem( $target );
				}
			} );

			toolbarController.registerEventHandler(
				event.data.toolbar.type,
				event.data.toolbar.id,
				'snaklistviewafterstopediting',
				function( event, toolbarcontroller ) {
					// Destroy the snakview toolbars:
					var $snaklistviewNode = $( event.target ),
						listview = $snaklistviewNode.data( 'snaklistview' )._listview,
						lia = listview.listItemAdapter();

					$.each( listview.items(), function( i, item ) {
						var snakview = lia.liInstance( $( item ) );
						toolbarcontroller.destroyToolbar( snakview.element.data( 'removetoolbar' ) );
					} );
				}
			);

			toolbarController.registerEventHandler(
				event.data.toolbar.type,
				event.data.toolbar.id,
				'snaklistviewdisable',
				function( event ) {
					var $snaklistviewNode = $( event.target ),
						listview = $snaklistviewNode.data( 'snaklistview' )._listview,
						lia = listview.listItemAdapter(),
						$parentView = $snaklistviewNode.closest( ':wikibase-statementview' ),
						parentView = null;

					if( $parentView.length ) {
						parentView = $parentView.data( 'statementview' );
					} else {
						$parentView = $snaklistviewNode.closest( ':wikibase-claimview' );
						parentView = $parentView.data( 'claimview' );
					}

					$.each( listview.items(), function( i, node ) {
						var $snakview = $( node ),
							snakview = lia.liInstance( $snakview ),
							removeToolbar = $snakview.data( 'removetoolbar' );

						// Item might be about to be removed not being a list item instance.
						if( !snakview || !removeToolbar ) {
							return true;
						}

						$snakview.data( 'removetoolbar' ).toolbar[parentView.option( 'disabled' )
							? 'disable'
							: 'enable'
						]();
					} );
				}
			);

		}
	}
} );

$.wikibase.toolbarcontroller.definition( 'movetoolbar', {
	id: 'claim-qualifiers-snak',
	selector: '.wb-claim-qualifiers',
	events: {
		'snakviewstartediting': function( event, toolbarController ) {
			var $snakview = $( event.target ),
				$snaklistview = $snakview.closest( ':wikibase-snaklistview' ),
				snaklistview = $snaklistview.data( 'snaklistview' );

			if( !snaklistview ) {
				return;
			}

			var $listview = $snaklistview.closest( ':wikibase-listview' );

			if( !$listview.parent().hasClass( 'wb-claim-qualifiers' ) ) {
				return;
			}

			var listview = $listview.data( 'listview' );

			if( $snaklistview.data( 'snaklistview' ).value() !== null ) {
				// Create toolbar for each snakview widget:
				$snakview.movetoolbar();

				var $topMostSnakview = listview.items().first().data( 'snaklistview' )
					._listview.items().first();
				var $bottomMostSnakview = listview.items().last().data( 'snaklistview' )
					._listview.items().last();

				if ( $topMostSnakview.get( 0 ) === $snakview.get( 0 ) ) {
					$snakview.data( 'movetoolbar' ).$btnMoveUp.data( 'toolbarbutton' ).disable();
				}

				if( $bottomMostSnakview.get( 0 ) === $snakview.get( 0 ) ) {
					$snakview.data( 'movetoolbar' ).$btnMoveDown.data( 'toolbarbutton' ).disable();
				}

				toolbarController.registerEventHandler(
					event.data.toolbar.type,
					event.data.toolbar.id,
					'snaklistviewafterstopediting',
					function( event, toolbarcontroller ) {
						// Destroy the snakview toolbars:
						var $snaklistviewNode = $( event.target ),
							listview = $snaklistviewNode.data( 'snaklistview' )._listview,
							lia = listview.listItemAdapter();

						$.each( listview.items(), function( i, item ) {
							var snakview = lia.liInstance( $( item ) );
							toolbarcontroller.destroyToolbar( snakview.element.data( 'movetoolbar' ) );
						} );

						// Remove obsolete event handlers attached to the node the toolbarcontroller has been
						// initialized on:
						$snaklistviewNode.closest( '.wb-claim-qualifiers' ).off( '.movetoolbar' );
					}
				);

				toolbarController.registerEventHandler(
					event.data.toolbar.type,
					event.data.toolbar.id,
					'movetoolbarup movetoolbardown',
					function( event ) {
						var $snakview = $( event.target ),
							$snaklistview = $snakview.closest( ':wikibase-snaklistview' );

						if( !$snaklistview.length ) {
							// Unrelated "move" action.
							return;
						}

						var snaklistview = $snaklistview.data( 'snaklistview' ),
							snaklistviewListview = snaklistview.$listview.data( 'listview' ),
							snaklistviewListviewLia = snaklistviewListview.listItemAdapter(),
							snak = snaklistviewListviewLia.liInstance( $snakview ).snak(),
							snakList = snaklistview.value(),
							$listview = $snaklistview.closest( ':wikibase-listview' ),
							listview = $listview.data( 'listview' ),
							action = ( event.type === 'movetoolbarup' ) ? 'moveUp' : 'moveDown';

						if( action === 'moveUp' && snakList.indexOf( snak ) !== 0 ) {
							// Snak is not in top of the snaklistview group the snaks featuring the same
							// property. Therefore, the snak is to be moved within the snaklistview.
							snaklistview.moveUp( snak );
						} else if( action === 'moveDown' && snakList.indexOf( snak ) !== snakList.length - 1 ) {
							// Move down snakview within a snaklistview.
							snaklistview.moveDown( snak );
						} else {
							// When issuing "move up" on a snak on top of a snak list, the whole snaklistview
							// has to be move; Same for "move down" on a snak at the bottom of a snak list.
							listview[action]( $snaklistview );
						}
					}
				);

				toolbarController.registerEventHandler(
					event.data.toolbar.type,
					event.data.toolbar.id,
					'movetoolbarup movetoolbardown listviewitemadded listviewitemremoved',
					function( event ) {
						// Disable "move up" button of the topmost snakview of the topmost snaklistview and the
						// "move down" button of the bottommost snakview of the bottommost snaklistview. All
						// other buttons shall be enabled.
						var $target = $( event.target ),
							$claimview = $target.closest( ':wikibase-statementview,:wikibase-claimview' ),
							claimview = $claimview.data( 'statementview' ) || $claimview.data( 'claimview' ),
							listview;

						if( event.type.indexOf( 'listview' ) !== 0 ) {
							var $snaklistview = $target.closest( ':wikibase-snaklistview' ),
								$listview = $snaklistview.closest( ':wikibase-listview' );
							listview = $listview.data( 'listview' );
						} else if( !$target.parent().hasClass( 'wb-claim-qualifiers' ) ) {
							// Do not react on snaklistview's listview event.
							return;
						} else {
							listview = $target.data( 'listview' );
						}

						if( !listview ) {
							// Unrelated "move" action.
							return;
						}

						var listviewItems = listview.items();

						listviewItems.each( function( i, snaklistviewNode ) {
							var snaklistview = $( snaklistviewNode ).data( 'snaklistview' );

							if( !snaklistview || !snaklistview.value() || !snaklistview.isInEditMode() ) {
								// Pending snaklistview: Remove the preceding "move down" button if it exists:
								return;
							}

							var snaklistviewItems = snaklistview._listview.items();

							snaklistviewItems.each( function( j, snakviewNode ) {
								var $snakview = $( snakviewNode ),
									toolbar = $snakview.data( 'movetoolbar' );

								// Pending snakviews do not feature a movetoolbar.
								if( toolbar ) {
									var btnUp = toolbar.$btnMoveUp.data( 'toolbarbutton' ),
										btnDown = toolbar.$btnMoveDown.data( 'toolbarbutton' ),
										isOverallFirst = ( i === 0 && j === 0 ),
										isLastInSnaklistview = ( j === snaklistviewItems.length - 1 ),
										isOverallLast = ( i === listviewItems.length - 1 && isLastInSnaklistview ),
										hasNextListItem = listviewItems.eq( i + 1 ).length > 0,
										nextSnaklist = ( hasNextListItem )
											? listviewItems.eq( i + 1 ).data( 'snaklistview' ).value()
											: null,
										nextListItemIsPending = hasNextListItem && (
											nextSnaklist === null
											|| !claimview._initialQualifiers.hasSnak( nextSnaklist.toArray()[0] )
										),
										isBeforePending = isLastInSnaklistview && nextListItemIsPending;

									btnUp[ ( isOverallFirst ) ? 'disable' : 'enable' ]();
									btnDown[ ( isOverallLast || isBeforePending ) ? 'disable' : 'enable' ]();
								}
							} );
						} );

						// Stop repeatedly triggering the event on the moved DOM node:
						event.stopImmediatePropagation();
					}
				);
			}
		}
	}
} );

// We have to override this here because $.widget sets it no matter what's in
// the prototype
$.wikibase.claimview.prototype.widgetBaseClass = 'wb-claimview';

}( mediaWiki, wikibase, jQuery ) );
