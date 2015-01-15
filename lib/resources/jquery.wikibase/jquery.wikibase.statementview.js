( function( mw, wb, $ ) {
	'use strict';

	var PARENT = $.ui.TemplatedWidget;

/**
 * View for displaying and editing `wikibase.datamodel.Statement` objects.
 * @see wikibase.datamodel.Statement
 * @class jQuery.wikibase.statementview
 * @extends jQuery.ui.TemplatedWidget
 * @uses jQuery.NativeEventHandler
 * @uses jQuery.ui.toggler
 * @uses jQuery.wikibase.listview
 * @uses jQuery.wikibase.listview.ListItemAdapter
 * @uses jQuery.wikibase.referenceview
 * @uses jQuery.wikibase.snakview
 * @uses jQuery.wikibase.snaklistview
 * @uses jQuery.wikibase.statementview.RankSelector
 * @uses mediaWiki
 * @uses wikibase.datamodel.Claim
 * @uses wikibase.datamodel.SnakList
 * @uses wikibase.datamodel.ReferenceList
 * @uses wikibase.datamodel.Statement
 * @uses wikibase.utilities.ClaimGuidGenerator
 * @uses wikibase.utilities.ui
 * @since 0.4
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 *
 * @param {Object} options
 * @param {wikibase.datamodel.Statement|null} [options.value=null]
 *        The `Statement` displayed by the view. May be set initially only.
 *        If `null`, the view will be switched to edit mode initially.
 * @param {wikibase.entityChangers.ClaimsChanger} options.claimsChanger
 *        Required to store the view's `Statement`.
 * @param {wikibase.store.EntityStore} options.entityStore
 *        Required for dynamically gathering `Entity`/`Property` information.
 * @param {wikibase.ValueViewBuilder} options.valueViewBuilder
 *        Required by the `snakview` interfacing a `snakview` "value" `Variation` to
 *        `jQuery.valueview`.
 * @param {wikibase.entityChangers.EntityChangersFactory} options.entityChangersFactory
 *        Required to store the `Reference`s gatherd from the `referenceview`s aggregated by the
 *        `statementview`.
 * @param {dataTypes.DataTypeStore} options.dataTypeStore
 *        Required by the `snakview` for retrieving and evaluating a proper `dataTypes.DataType`
 *        object when interacting on a "value" `Variation`.
 * @param {Object} [options.predefined={ mainSnak: false }]
 *        Allows to predefine certain aspects of the `Statement` to be created from the view. If
 *        this option is omitted, an empty view is created. A common use-case is adding a value to a
 *        property existing already by specifying, for example: `{ mainSnak.property: 'P1' }`.
 * @param {Object} [options.locked={ mainSnak: false }]
 *        Elements that shall be locked and may not be changed by user interaction.
 * @param {string} [optionshelpMessage=mw.msg( 'wikibase-claimview-snak-new-tooltip' )]
 *        End-user message explaining how to use the `statementview` widget. The message is most
 *        likely to be used inside the tooltip of the toolbar corresponding to the `statementview`.
 */
/**
 * @event startediting
 * Triggered when starting the view's edit mode.
 * @param {jQuery.Event} event
 */
/**
 * @event afterstartediting
 * Triggered after having started the view's edit mode.
 * @param {jQuery.Event} event
 */
/**
 * @event stopediting
 * Triggered when stopping the view's edit mode.
 * @param {jQuery.Event} event
 * @param {boolean} dropValue If true, the value from before edit mode has been started will be
 *        reinstated (basically, a cancel/save switch).
 */
/**
 * @event afterstopediting
 * Triggered after having stopped the view's edit mode.
 * @param {jQuery.Event} event
 * @param {boolean} dropValue If true, the value from before edit mode has been started has been
 * reinstated (basically, a cancel/save switch).
 */
/**
 * @event afterremove
 * Triggered after a `referenceview` has been remove from the `statementview`'s list of
 * `referenceview`s.
 * @param {jQuery.Event} event
 */
/**
 * @event change
 * Triggered whenever the view's content is changed.
 * @param {jQuery.Event} event
 */
/**
 * @event toggleerror
 * Triggered when an error occurred or is resolved.
 * @param {jQuery.Event} event
 * @param {wikibase.api.RepoApiError} [error] `wikikibase.api.RepoApiError` object if an error
 *        occurred, `undefined` if the current error state is resolved.
 */
$.widget( 'wikibase.statementview', PARENT, {
	/**
	 * @inheritdoc
	 * @protected
	 * @readonly
	 */
	options: {
		template: 'wikibase-statementview',
		templateParams: [
			function() { // GUID
				return ( this._statement && this._statement.getClaim().getGuid() ) || 'new';
			},
			function() { // Rank selector
				return $( '<div/>' );
			},
			function() { // Main snak
				return $( '<div/>' );
			},
			'', // Qualifiers
			'', // Toolbar placeholder
			'', // References heading
			'' // List of references
		],
		templateShortCuts: {
			$rankSelector: '.wikibase-statementview-rankselector',
			$mainSnak: '.wikibase-statementview-mainsnak > :first-child',
			$qualifiers: '.wikibase-statementview-qualifiers',
			$refsHeading: '.wikibase-statementview-references-heading',
			$references: '.wikibase-statementview-references'
		},
		value: null,
		claimsChanger: null,
		dataTypeStore: null,
		entityChangersFactory: null,
		predefined: {
			mainSnak: false
		},
		locked: {
			mainSnak: false
		},
		helpMessage: mw.msg( 'wikibase-claimview-snak-new-tooltip' )
	},

	/**
	 * @property {jQuery.wikibase.statementview.RankSelector}
	 * @private
	 */
	_rankSelector: null,

	/**
	 * Shortcut to the `ListItemAdapter` in use in the `listview` managing the `referenceview`s.
	 * @property {jQuery.wikibase.listview.ListItemAdapter}
	 * @private
	 */
	_referenceviewLia: null,

	/**
	 * Shortcut to the `listview` managing the `referenceview`s.
	 * @property {jQuery.wikibase.listview}
	 * @private
	 */
	_referencesListview: null,

	/**
	 * The `Statement` represented by this view. This is the `Statement` actually stored in the data
	 * base. Updates to the `Statement` not yet stored are not reflected in this object.
	 * @property {wikibase.datamodel.Statement|null}
	 * @private
	 */
	_statement: null,

	/**
	 * Reference to the `listview` widget managing the qualifier `snaklistview`s.
	 * @property {jQuery.wikibase.listview}
	 * @private
	 */
	_qualifiers: null,

	/**
	 * Caches the `SnakList` of the qualifiers the `statementview` has been initialized with. The
	 * qualifiers are split into groups featuring the same `Property`. Removing one of those groups
	 * results in losing the reference to those qualifiers. Therefore, `_initialQualifiers` is used
	 * to rebuild the list of qualifiers when cancelling and is used to query whether the qualifiers
	 * represent the initial state.
	 * @property {wikibase.datamodel.SnakList}
	 * @private
	 */
	_initialQualifiers: null,

	/**
	 * @property {wikibase.entityChangers.ReferencesChanger}
	 * @private
	 */
	_referencesChanger: null,

	/**
	 * @inheritdoc
	 * @protected
	 *
	 * @throws {Error} if a required option is not specified properly.
	 */
	_create: function() {
		if(
			!this.options.entityStore
			|| !this.options.valueViewBuilder
			|| !this.options.claimsChanger
			|| !this.options.entityChangersFactory
			|| !this.options.dataTypeStore
		) {
			throw new Error( 'Required option not specified properly' );
		}

		PARENT.prototype._create.call( this );

		this._statement = this.options.value;

		this._createRankSelector( this._statement ? this._statement.getRank() : null );
		this._createMainSnak( this._statement
			? this._statement.getClaim().getMainSnak()
			: this.option( 'predefined' ).mainSnak || null
		);

		this._initialQualifiers = this._statement
			? this._statement.getClaim().getQualifiers()
			: new wb.datamodel.SnakList();

		// TODO: Allow adding qualifiers when adding a new claim.
		if( this._statement && this._initialQualifiers.length ) {
			this._createQualifiersListview( this._initialQualifiers );
		}

		this._referencesChanger = this.options.entityChangersFactory.getReferencesChanger();
		this._createReferences( this._statement );

		this._updateHelpMessage();
	},

	/**
	 * @since 0.5
	 * @private
	 *
	 * @param {number} rank
	 */
	_createRankSelector: function( rank ) {
		var $rankSelector = this.$rankSelector.children().first();
		this._rankSelector = new $.wikibase.statementview.RankSelector( {
			rank: rank,
			templateParams: ['ui-state-disabled', '', '']
		}, $rankSelector );

		var self = this,
			changeEvent = ( this._rankSelector.widgetEventPrefix + 'afterchange' ).toLowerCase();

		this.$rankSelector.on( changeEvent + '.' + this.widgetName, function( event ) {
			if( self.value() ) {
				self._trigger( 'change' );
			}
		} );

		this.element
		.on( this.widgetEventPrefix + 'toggleerror.' + this.widgetName, function( event, error ) {
			if( !error ) {
				self._rankSelector.enable();
			}
		} )
		.on(
			this.widgetEventPrefix + 'afterstopediting.' + this.widgetName,
			function( event, dropValue ) {
				// FIXME: This should be the responsibility of the rankSelector
				$rankSelector.removeClass( 'ui-state-default' );
				if( dropValue && self._statement ) {
					self._rankSelector.rank( self._statement.getRank() );
				}
				self._rankSelector.disable();
			}
		);
	},

	/**
	 * @private
	 *
	 * @param {wikibase.datamodel.Snak|null} [snak=null]
	 */
	_createMainSnak: function( snak ) {
		var self = this;

		this.$mainSnak
		.on(
			[
				'snakviewchange.' + this.widgetName,
				'snakviewafterstartediting.' + this.widgetName
			].join( ' ' ),
			function( event, status ) {
				event.stopPropagation();
				self._trigger( 'change' );
			}
		)
		.on( 'snakviewstopediting.' + this.widgetName, function( event ) {
			event.stopPropagation();
		} );

		this.$mainSnak.snakview( {
			value: snak || null,
			locked: this.options.locked.mainSnak,
			autoStartEditing: false,
			dataTypeStore: this.options.dataTypeStore,
			entityStore: this.options.entityStore,
			valueViewBuilder: this.options.valueViewBuilder
		} );
	},

	/**
	 * @private
	 *
	 * @param {wikibase.datamodel.SnakList|null} [qualifiers=null]
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
						dataTypeStore: self.options.dataTypeStore,
						entityStore: self.options.entityStore,
						valueViewBuilder: self.options.valueViewBuilder
					};
				}
			} ),
			value: groupedQualifierSnaks
		} )
		.on( 'snaklistviewstopediting.' + this.widgetName, function( event, dropValue ) {
			event.stopPropagation();
		} )
		.on( 'snaklistviewchange.' + this.widgetName
			+ ' listviewafteritemmove.' + this.widgetName,
			function( event ) {
				event.stopPropagation();
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
	},

	/**
	 * @private
	 *
	 * @param {wikibase.datamodel.Statement} [statement]
	 */
	_createReferences: function( statement ) {
		if( !statement ) {
			return;
		}

		var self = this,
			references = statement.getReferences();

		var $listview = this.$references.children();
		if( !$listview.length ) {
			$listview = $( '<div/>' ).prependTo( this.$references );
		}

		$listview.listview( {
			listItemAdapter: new $.wikibase.listview.ListItemAdapter( {
				listItemWidget: $.wikibase.referenceview,
				newItemOptionsFn: function( value ) {
					return {
						value: value || null,
						statementGuid: self.value().getClaim().getGuid(),
						dataTypeStore: self.options.dataTypeStore,
						entityStore: self.options.entityStore,
						valueViewBuilder: self.options.valueViewBuilder,
						referencesChanger: self._referencesChanger
					};
				}
			} ),
			value: references.toArray()
		} );

		this._referencesListview = $listview.data( 'listview' );

		this._referenceviewLia = this._referencesListview.listItemAdapter();

		$listview
		.on( 'listviewitemadded listviewitemremoved', function( event, value, $li ) {
			if( event.target === $listview.get( 0 ) ) {
				self._drawReferencesCounter();
			}
		} )
		.on( 'listviewenternewitem', function( event, $newLi ) {
			// Enter first item into the referenceview.
			self._referenceviewLia.liInstance( $newLi ).enterNewItem();

			var lia = self._referenceviewLia,
				liInstance = lia.liInstance( $newLi );

			if ( !liInstance.value() ) {
				$newLi
				.on( lia.prefixedEvent( 'afterstopediting' ), function( event, dropValue ) {
					if( dropValue ) {
						liInstance.destroy();
						$newLi.remove();
						self._drawReferencesCounter();
					} else {
						var newReferenceWithHash = liInstance.value();

						// Destroy new reference input form and add reference to list
						liInstance.destroy();
						$newLi.remove();

						// Display new reference with final GUID
						self._addReference( newReferenceWithHash );
					}
				} );
			}
		} );

		// Collapse references if there is at least one.
		if ( this._referencesListview.items().length > 0 ) {
			this.$references.css( 'display', 'none' );
		}

		// toggle for references section:
		var $toggler = $( '<a/>' ).toggler( { $subject: this.$references } );

		if( this.$refsHeading.text() ) {
			$toggler.find( '.ui-toggler-label' ).text( this.$refsHeading.text() );
			this.$refsHeading.html( $toggler );
		} else {
			this.$refsHeading.html( $toggler );
			this._drawReferencesCounter();
		}
	},

	/**
	 * Updates the `helpMessage` option according to whether the main `Snak`'s `Property` is
	 * predefined.
	 * @private
	 */
	_updateHelpMessage: function() {
		if( !this._statement && !this.options.predefined.mainSnak ) {
			return;
		}

		var property = this._statement
			? this._statement.getClaim().getMainSnak().getPropertyId()
			: this.options.predefined.mainSnak.property;

		var deferred = $.Deferred(),
			helpMessage = this.options.helpMessage;

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
	},

	/**
	 * @inheritdoc
	 */
	destroy: function() {
		this._rankSelector.destroy();
		this.$rankSelector.off( '.' + this.widgetName );

		this.$mainSnak.snakview( 'destroy' );
		this.$mainSnak.off( '.' + this.widgetName );

		this._destroyQualifiersListView();

		PARENT.prototype.destroy.call( this );
	},

	/**
	 * @private
	 */
	_destroyQualifiersListView: function() {
		if( this._qualifiers ) {
			this._qualifiers.destroy();
			this.$qualifiers
				.off( '.' + this.widgetName )
				.empty();
			this._qualifiers = null;
		}
	},

	/**
	 * @return {boolean}
	 */
	isInitialValue: function() {
		if( this._statement ) {
			if( this._statement.getRank() !== this._rankSelector.rank() ) {
				return false;
			}

			var snaklistviews = ( this._qualifiers ) ? this._qualifiers.value() : [],
				qualifiers = new wb.datamodel.SnakList();

			// Generate a SnakList object featuring all current qualifier snaks to be able to
			// compare it to the SnakList object the claimview has been initialized with:
			if( snaklistviews.length ) {
				for( var i = 0; i < snaklistviews.length; i++ ) {
					if( snaklistviews[i].value() ) {
						qualifiers.merge( snaklistviews[i].value() );
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
	 * Instantiates a `Statement` with the `statementview`'s current value.
	 * @private
	 *
	 * @param {string} guid
	 * @return {wikibase.datamodel.Statement}
	 */
	_instantiateStatement: function( guid ) {
		var qualifiers = new wb.datamodel.SnakList(),
			snaklistviews = this._qualifiers ? this._qualifiers.value() : [];

		// Combine qualifiers grouped by property to a single SnakList:
		for( var i = 0; i < snaklistviews.length; i++ ) {
			qualifiers.merge( snaklistviews[i].value() );
		}

		return new wb.datamodel.Statement(
			new wb.datamodel.Claim(
				this.$mainSnak.data( 'snakview' ).snak(),
				qualifiers,
				guid
			),
			new wb.datamodel.ReferenceList( this._getReferences() ),
			this._rankSelector.rank()
		);
	},

	/**
	 * Adds a `Reference` and renders it in the view.
	 * @private
	 *
	 * @param {wikibase.datamodel.Reference} reference
	 */
	_addReference: function( reference ) {
		this._referencesListview.addItem( reference );
	},

	/**
	 * Returns all `Reference`s currently specified in the view (including all pending changes).
	 * @private
	 *
	 * @return {wikibase.datamodel.Reference[]}
	 */
	_getReferences: function() {
		var self = this,
			references = [];

		// If the statement is pending (not yet stored), the listview widget for the references is
		// not defined.
		if ( !this._referencesListview ) {
			return references;
		}

		$.each( this._referencesListview.items(), function( i, item ) {
			var referenceview = self._referenceviewLia.liInstance( $( item ) );
			references.push( referenceview.value() );
		} );

		return references;
	},

	/**
	 * Removes a `referenceview` from the view's list of `referenceview`s.
	 *
	 * @param {jQuery.wikibase.referenceview} referenceview
	 */
	remove: function( referenceview ) {
		var self = this;

		referenceview.disable();

		this._referencesChanger.removeReference(
			this.value().getClaim().getGuid(),
			referenceview.value()
		)
		.done( function() {
			self._referencesListview.removeItem( referenceview.element );
			self._trigger( 'afterremove' );
		} ).fail( function( error ) {
			referenceview.enable();
			referenceview.setError( error );
		} );
	},

	/**
	 * Returns the current `Statement` represented by the view. If `null` is returned, the view's
	 * `Statement` has not yet been stored.
	 *
	 * @return {wikibase.datamodel.Statement|null}
	 */
	value: function() {
		return this._statement;
	},

	/**
	 * Updates the visual `Reference`s counter.
	 * @private
	 */
	_drawReferencesCounter: function() {
		var numberOfValues = this._referencesListview.nonEmptyItems().length,
			numberOfPendingValues = this._referencesListview.items().length - numberOfValues;

		// build a nice counter, displaying fixed and pending values:
		var $counterMsg = wb.utilities.ui.buildPendingCounter(
			numberOfValues,
			numberOfPendingValues,
			'wikibase-statementview-referencesheading-pendingcountersubject',
			'wikibase-statementview-referencesheading-pendingcountertooltip' );

		// update counter, don't touch the toggle!
		this.$refsHeading.find( '.ui-toggler-label' ).empty().append( $counterMsg );
	},

	/**
	 * Stops the view's edit mode.
	 *
	 * @param {boolean} [dropValue=false] If `true`, the value from before edit mode has been
	 *        started will be reinstated--basically a cancel/save switch.
	 *
	 * @return {undefined}
	 */
	stopEditing: $.NativeEventHandler( 'stopEditing', {
		// don't stop edit mode or trigger event if not in edit mode currently:
		initially: function( e, dropValue ) {
			if(
				!this.isInEditMode() || ( !this.isValid() || this.isInitialValue() ) && !dropValue
			) {
				e.cancel();
			}

			this.element.removeClass( 'wb-error' );
		},
		// start edit mode if custom event handlers didn't prevent default:
		natively: function( e, dropValue ) {
			var self = this;

			this.disable();
			this._rankSelector.disable();

			function stopEditing() {
				if( self.$mainSnak.data( 'snakview' ) ) {
					self.$mainSnak.data( 'snakview' ).stopEditing( dropValue );
				}

				self._stopEditingQualifiers( dropValue );

				self._isInEditMode = false;
				self.enable();

				self.element.removeClass( 'wb-edit' );

				// transform toolbar and snak view after save complete
				self._trigger( 'afterstopediting', null, [dropValue] );
			}

			if( dropValue ) {
				stopEditing();
			} else {
				// editing an existing claim
				this._saveStatementApiCall()
				.done( stopEditing )
				.fail( function( error ) {
					self.enable();
					self.setError( error );
				} );
			}
		}
	} ),

	/**
	 * @private
	 *
	 * @param {boolean} [dropValue=false]
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
						this._initialQualifiers.merge( snaklistviews[i].value() );
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
	 * @private
	 *
	 * @return {Object} jQuery.Promise
	 * @return {Function} return.done
	 * @return {wikibase.datamodel.Statement} return.done.statement The saved statement-
	 * @return {Function} return.fail
	 * @return {wikibase.api.RepoApiError} return.fail.error
	 */
	_saveStatementApiCall: function() {
		var self = this,
			guid;

		if( this.value() ) {
			guid = this.value().getClaim().getGuid();
		} else {
			var guidGenerator = new wb.utilities.ClaimGuidGenerator();
			guid = guidGenerator.newGuid( mw.config.get( 'wbEntityId' ) );
		}

		return this.option( 'claimsChanger' ).setStatement( this._instantiateStatement( guid ) )
		.done( function( savedStatement ) {
			// Update model of represented Statement:
			self._statement = savedStatement;
		} );
	},

	/**
	 * Exits edit mode and restores the value from before the edit mode has been started.
	 * (Shortcut to `this.stopEditing( true )`)
	 *
	 * @return {undefined}
	 */
	cancelEditing: function() {
		return this.stopEditing( true );
	},

	/**
	 * Starts the view's edit mode.
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
			var self = this;

			this.$mainSnak.one( 'snakviewafterstartediting', function() {
				if( !self._qualifiers && self._statement ) {
					self._createQualifiersListview();
				}

				// Start edit mode of all qualifiers:
				if( self._qualifiers ) {
					var snaklistviews = self._qualifiers.value();
					if( snaklistviews.length ) {
						for( var i = 0; i < snaklistviews.length; i++ ) {
							snaklistviews[i].startEditing();
						}
					}
				}

				self.element.addClass( 'wb-edit' );
				self._isInEditMode = true;

				// FIXME: This should be the responsibility of the rankSelector
				self._rankSelector.element.addClass( 'ui-state-default' );
				if( !self._statement ) {
					self._rankSelector.rank( wb.datamodel.Statement.RANK.NORMAL );
				}
				self._rankSelector.enable();

				self._trigger( 'afterstartediting' );
			} );

			this.$mainSnak.data( 'snakview' ).startEditing();
		}
	} ),

	/**
	 * @return {boolean}
	 */
	isInEditMode: function() {
		return this._isInEditMode;
	},

	/**
	 * Sets/removes error state from the view.
	 *
	 * @param {wikibase.api.RepoApiError} [error]
	 */
	setError: function( error ) {
		if( error ) {
			this.element.addClass( 'wb-error' );
			this._trigger( 'toggleerror', null, [ error ] );
		} else {
			this.element.removeClass( 'wb-error' );
			this._trigger( 'toggleerror' );
		}
	},

	/**
	 * @return {boolean}
	 */
	isValid: function() {
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
			this._instantiateStatement( null );
		} catch( e ) {
			return false;
		}

		return true;
	},

	/**
	 * @inheritdoc
	 * @protected
	 *
	 * @throws {Error} when tyring to set `value` option.
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
	},

	/**
	 * @inheritdoc
	 */
	focus: function() {
		this.$mainSnak.data( 'snakview' ).focus();
	}
} );

}( mediaWiki, wikibase, jQuery ) );
