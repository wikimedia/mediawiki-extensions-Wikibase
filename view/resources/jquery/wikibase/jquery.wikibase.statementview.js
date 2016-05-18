( function( mw, wb, $ ) {
	'use strict';

	var PARENT = $.ui.EditableTemplatedWidget;

/**
 * View for displaying and editing `wikibase.datamodel.Statement` objects.
 * @see wikibase.datamodel.Statement
 * @class jQuery.wikibase.statementview
 * @extends jQuery.ui.EditableTemplatedWidget
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
 * @uses wikibase.utilities.ui
 * @since 0.4
 * @license GPL-2.0+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 *
 * @param {Object} options
 * @param {wikibase.datamodel.Statement|null} [options.value=null]
 *        The `Statement` displayed by the view. May be set initially only and gets updated
 *        automatically if changes to the `Statement` are saved.
 *        If `null`, the view will be switched to edit mode initially.
 * @param {Function} options.buildReferenceListItemAdapter
 * @param {Function} options.buildSnakView
 * @param {wikibase.utilities.ClaimGuidGenerator} options.guidGenerator
 *        Required for dynamically generating GUIDs for new `Statement`s.
 * @param {wikibase.entityChangers.ClaimsChanger} options.claimsChanger
 *        Required to store the view's `Statement`.
 * @param {wikibase.entityIdFormatter.EntityIdPlainFormatter} options.entityIdPlainFormatter
 *        Required for dynamically rendering plain text references to `Entity`s.
 * @param {Object} [options.predefined={ mainSnak: false }]
 *        Allows to predefine certain aspects of the `Statement` to be created from the view. If
 *        this option is omitted, an empty view is created. A common use-case is adding a value to a
 *        property existing already by specifying, for example: `{ mainSnak.property: 'P1' }`.
 * @param {jQuery.wikibase.listview.ListItemAdapter} options.qualifiersListItemAdapter
 * @param {Object} [options.locked={ mainSnak: false }]
 *        Elements that shall be locked and may not be changed by user interaction.
 * @param {string} [options.helpMessage=mw.msg( 'wikibase-claimview-snak-new-tooltip' )]
 *        End-user message explaining how to use the `statementview` widget. The message is most
 *        likely to be used inside the tooltip of the toolbar corresponding to the `statementview`.
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
$.widget( 'wikibase.statementview', PARENT, {
	/**
	 * @inheritdoc
	 * @protected
	 */
	options: {
		template: 'wikibase-statementview',
		templateParams: [
			function() { // GUID
				return ( this.options.value && this.options.value.getClaim().getGuid() ) || 'new';
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
			'', // List of references
			'' // wikibase-initially-collapsed for wikibase-statementview-references
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
		entityIdPlainFormatter: null,
		predefined: {
			mainSnak: false
		},
		locked: {
			mainSnak: false
		},
		helpMessage: mw.msg( 'wikibase-claimview-snak-new-tooltip' )
	},

	/**
	 * @property {jQuery.wikibase.snakview|null}
	 * @private
	 */
	_mainSnakSnakView: null,

	/**
	 * @property {jQuery.wikibase.statementview.RankSelector|null}
	 * @private
	 */
	_rankSelector: null,

	/**
	 * Shortcut to the `listview` managing the `referenceview`s.
	 * @property {jQuery.wikibase.listview|null}
	 * @private
	 */
	_referencesListview: null,

	/**
	 * @property {boolean}
	 * @private
	 */
	_ignoreReferencesListviewChanges: false,

	/**
	 * Reference to the `listview` widget managing the qualifier `snaklistview`s.
	 * @property {jQuery.wikibase.listview|null}
	 * @private
	 */
	_qualifiers: null,

	/**
	 * Reference to the `toggler` widget managing expanding/collapsing
	 * @property {jQuery}
	 * @private
	 */
	_$toggler: null,

	/**
	 * @inheritdoc
	 * @protected
	 *
	 * @throws {Error} if a required option is not specified properly.
	 */
	_create: function() {
		if ( !this.options.buildReferenceListItemAdapter
			|| !this.options.buildSnakView
			|| !this.options.claimsChanger
			|| !this.options.entityIdPlainFormatter
			|| !this.options.guidGenerator
			|| !this.options.qualifiersListItemAdapter
		) {
			throw new Error( 'Required option not specified properly' );
		}

		var isEmpty = this.element.is( ':empty' );
		PARENT.prototype._create.call( this );

		if ( isEmpty ) {
			this.draw();
		} else {
			this._createReferencesToggler();
		}
	},

	/**
	 * @since 0.5
	 * @private
	 *
	 * @param {number} rank
	 */
	_createRankSelector: function( rank ) {
		if ( this._rankSelector ) {
			return;
		}

		var $rankSelector = this.$rankSelector.children().first();
		this._rankSelector = new $.wikibase.statementview.RankSelector( {
			value: rank,
			templateParams: ['ui-state-disabled', '', ''],
			// TODO: Directionality should be determined on entityview level and forwarded to here
			isRTL: $( 'html' ).prop( 'dir' ) === 'rtl'
		}, $rankSelector );

		var self = this,
			changeEvent = ( this._rankSelector.widgetEventPrefix + 'afterchange' ).toLowerCase();

		this.$rankSelector.on( changeEvent + '.' + this.widgetName, function( event ) {
			if ( self.value() ) {
				self._trigger( 'change' );
			}
		} );
	},

	/**
	 * @private
	 */
	_createMainSnak: function() {
		if ( this.$mainSnak.data( 'snakview' ) ) {
			this._mainSnakSnakView = this.$mainSnak.data( 'snakview' );
			return;
		}

		var snak = this.options.value
			? this.options.value.getClaim().getMainSnak()
			: this.options.predefined.mainSnak || null;
		var self = this;

		this.$mainSnak
		.on( 'snakviewchange.' + this.widgetName, function( event, status ) {
			event.stopPropagation();
			self._trigger( 'change' );
		} )
		.on( 'snakviewstopediting.' + this.widgetName, function( event ) {
			event.stopPropagation();
		} );

		this._mainSnakSnakView = this.options.buildSnakView(
			{
				locked: this.options.locked.mainSnak,
				autoStartEditing: false
			},
			snak,
			this.$mainSnak
		);
	},

	/**
	 * @private
	 *
	 * @param {wikibase.datamodel.SnakList|null} [qualifiers=null]
	 */
	_createQualifiersListview: function( qualifiers ) {
		if ( this._qualifiers ) {
			return;
		}

		var self = this,
			groupedQualifierSnaks = null;

		// Group qualifiers by property id:
		if ( qualifiers && qualifiers.length ) {
			groupedQualifierSnaks = qualifiers.getGroupedSnakLists();
		}

		// Using the property id, qualifier snaks are split into groups of snaklistviews. These
		// snaklistviews are managed in a listview:
		var $qualifiers = this.$qualifiers.children();
		if ( !$qualifiers.length ) {
			$qualifiers = $( '<div/>' ).prependTo( this.$qualifiers );
		}
		$qualifiers.listview( {
			listItemAdapter: this.options.qualifiersListItemAdapter,
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
			if ( event.target === self._qualifiers.element.get( 0 ) ) {
				self._trigger( 'change' );
				return;
			}

			// Check if last snaklistview of a qualifier listview item has been removed and
			// remove the listview item if so:
			var $snaklistview = $( event.target ).closest( ':wikibase-snaklistview' ),
				snaklistview = $snaklistview.data( 'snaklistview' );

			if ( !snaklistview.value().length ) {
				self._qualifiers.removeItem( snaklistview.element );
			}
		} );

		this._qualifiers = $qualifiers.data( 'listview' );
	},

	/**
	 * @private
	 *
	 * @param {wikibase.datamodel.Reference[]} [references]
	 */
	_createReferencesListview: function( references ) {
		var self = this;

		var $listview = this.$references.children( '.wikibase-listview' );
		if ( !$listview.length ) {
			$listview = $( '<div/>' ).prependTo( this.$references );
		} else if ( $listview.data( 'listview' ) ) {
			return;
		}

		var lia = this.options.buildReferenceListItemAdapter(
			this.options.value ? this.options.value.getClaim().getGuid() : null
		);

		$listview.listview( {
			listItemAdapter: lia,
			value: references
		} );

		this._referencesListview = $listview.data( 'listview' );

		$listview
		.on( 'listviewitemadded listviewitemremoved', function( event, value, $li ) {
			if ( self._ignoreReferencesListviewChanges ) {
				return;
			}
			if ( event.target === $listview[0] ) {
				self._drawReferencesCounter();
			}
			self._trigger( 'change' );
		} )
		.on( lia.prefixedEvent( 'change.' + this.widgetName ),
			function( event ) {
				event.stopPropagation();
				self._trigger( 'change' );
			} )
		.on( 'listviewenternewitem', function( event, $newLi ) {
			if ( event.target !== $listview[0] ) {
				return;
			}

			// Enter first item into the referenceview.
			lia.liInstance( $newLi ).enterNewItem();

			var liInstance = lia.liInstance( $newLi );

			if ( !liInstance.value() ) {
				$newLi
				.on( lia.prefixedEvent( 'afterstopediting' ), function( event, dropValue ) {
					if ( !dropValue ) {
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

		this._createReferencesToggler();
	},

	_createReferencesToggler: function() {
		if ( this._$toggler ) {
			return;
		}

		var expanded;

		if ( this._referencesListview ) {
			expanded = this._referencesListview.items().length === 0;
			this.$references.toggleClass( 'wikibase-initially-collapsed', !expanded );
		} else {
			expanded = !this.$references.hasClass( 'wikibase-initially-collapsed' );
		}

		// toggle for references section:
		this._$toggler = $( '<a/>' ).toggler( {
			$subject: this.$references,
			visible: expanded
		} );

		if ( this.$refsHeading.text() ) {
			this._$toggler.find( '.ui-toggler-label' ).text( this.$refsHeading.text() );
			this.$refsHeading.html( this._$toggler );
		} else {
			this.$refsHeading.html( this._$toggler );
			this._drawReferencesCounter();
		}
	},

	/**
	 * @inheritdoc
	 */
	getHelpMessage: function() {
		var deferred = $.Deferred(),
			helpMessage = this.options.helpMessage;

		if ( !this.options.value && !this.options.predefined.mainSnak ) {
			deferred.resolve( helpMessage );
		} else {
			var property = this.options.value
				? this.options.value.getClaim().getMainSnak().getPropertyId()
				: this.options.predefined.mainSnak.property;

			if ( property ) {
				this.options.entityIdPlainFormatter.format( property ).done( function( formattedEntityId ) {
					deferred.resolve( mw.msg( 'wikibase-claimview-snak-tooltip', formattedEntityId ) );
				} );
			} else {
				deferred.resolve( helpMessage );
			}
		}

		return deferred.promise();
	},

	/**
	 * @inheritdoc
	 */
	destroy: function() {
		if ( this._rankSelector ) {
			this._rankSelector.destroy();
			this.$rankSelector.off( '.' + this.widgetName );
			this._rankSelector = null;
		}

		if ( this._mainSnakSnakView ) {
			this._mainSnakSnakView.destroy();
			this.$mainSnak.off( '.' + this.widgetName );
			this._mainSnakSnakView = null;
		}

		if ( this._qualifiers ) {
			this._destroyQualifiersListView();
		}
		if ( this._referencesListview ) {
			this._destroyReferencesListview();
		}

		PARENT.prototype.destroy.call( this );
	},

	/**
	 * @private
	 */
	_destroyQualifiersListView: function() {
		this._qualifiers.destroy();
		this.$qualifiers
			.off( '.' + this.widgetName )
			.empty();
		this._qualifiers = null;
	},

	/**
	 * @private
	 */
	_destroyReferencesListview: function() {
		this._referencesListview.destroy();
		this.$references
			.off( '.' + this.widgetName )
			.empty();
		this._referencesListview = null;
	},

	/**
	 * @inheritdoc
	 */
	draw: function() {
		this._createRankSelector( this.options.value
			? this.options.value.getRank()
			: wb.datamodel.Statement.RANK.NORMAL
		);
		this._createMainSnak();

		if ( this.isInEditMode()
				|| this.options.value
					&& this.options.value.getClaim().getQualifiers().length
			) {
				this._createQualifiersListview(
					this.options.value
						? this.options.value.getClaim().getQualifiers()
						: new wb.datamodel.SnakList()
				);
			}
		this._createReferencesListview(
			this.options.value ? this.options.value.getReferences().toArray() : []
		);

		return $.Deferred().resolve().promise();
	},

	/**
	 * @see jQuery.ui.EditableTemplatedWidget.isInitialValue
	 * @return {boolean}
	 */
	isInitialValue: function() {
		if ( !this.isInEditMode() ) {
			mw.log.warn( 'statementview::isInitialValue should only be called in edit mode' );
			return true;
		}

		if ( this.options.value ) {
			if ( !this._rankSelector.isInitialValue() ) {
				return false;
			}

			var qualifiers = this._getQualifiers();
			if ( !qualifiers.equals( this.options.value.getClaim().getQualifiers() ) ) {
				return false;
			}

			var references = this._getReferences();
			if ( !references.equals( this.options.value.getReferences() ) ) {
				return false;
			}
		}

		return this._mainSnakSnakView.isInitialValue();
	},

	/**
	 * Instantiates a `Statement` with the `statementview`'s current value.
	 *
	 * @private
	 *
	 * @param {string} guid
	 * @return {wikibase.datamodel.Statement|null}
	 */
	_instantiateStatement: function( guid ) {
		if ( !this.isInEditMode() ) {
			mw.log.warn( 'statementview::_instantiateStatement should only be called in edit mode' );
			return null;
		}

		var mainSnak = this._mainSnakSnakView.snak();

		if ( !mainSnak ) {
			return null;
		}

		var qualifiers = this._getQualifiers();

		return new wb.datamodel.Statement(
			new wb.datamodel.Claim( mainSnak, qualifiers, guid ),
			this._getReferences(),
			this._rankSelector.value()
		);
	},

	/**
	 * Adds a `Reference` and renders it in the view.
	 *
	 * @private
	 *
	 * @param {wikibase.datamodel.Reference} reference
	 */
	_addReference: function( reference ) {
		this._referencesListview.addItem( reference );
	},

	/**
	 * @private
	 *
	 * @return {wikibase.datamodel.SnakList}
	 */
	_getQualifiers: function() {
		var qualifiers = new wb.datamodel.SnakList();

		if ( this._qualifiers ) {
			var snaklistviews = this._qualifiers.value();

			// Combine qualifiers grouped by property to a single SnakList:
			for ( var i = 0; i < snaklistviews.length; i++ ) {
				qualifiers.merge( snaklistviews[i].value() );
			}
		}

		return qualifiers;
	},

	/**
	 * Returns all `Reference`s currently specified in the view (including all pending changes).
	 *
	 * @private
	 *
	 * @return {wikibase.datamodel.ReferenceList}
	 */
	_getReferences: function() {
		return new wb.datamodel.ReferenceList(
			$.map( this._referencesListview.value(), function( referenceview ) {
				return referenceview.value();
			} )
		);
	},

	/**
	 * Returns the current `Statement` represented by the view, considering all pending changes not
	 * yet stored. Use `this.option( 'value' )` to retrieve the stored/original `Statement`.
	 *
	 * @return {wikibase.datamodel.Statement|null}
	 */
	value: function() {
		if ( this.isInEditMode() ) {
			var guid = this.options.value ? this.options.value.getClaim().getGuid() : null;
			return this._instantiateStatement( guid );
		} else {
			return this.options.value;
		}
	},

	/**
	 * Updates the visual `Reference`s counter.
	 *
	 * @private
	 */
	_drawReferencesCounter: function() {
		var numberOfValues = 0,
			numberOfPendingValues = 0;

		if ( this._referencesListview ) {
			numberOfPendingValues = this._referencesListview.items().filter( '.wb-reference-new' ).length;
			numberOfValues = this._referencesListview.items().length - numberOfPendingValues;
		}

		// build a nice counter, displaying fixed and pending values:
		var $counterMsg = wb.utilities.ui.buildPendingCounter(
			numberOfValues,
			numberOfPendingValues,
			'wikibase-statementview-references-counter',
			'wikibase-statementview-referencesheading-pendingcountertooltip' );

		// update counter, don't touch the toggle!
		this.$refsHeading.find( '.ui-toggler-label' ).empty().append( $counterMsg );
	},

	/**
	 * @inheritdoc
	 */
	startEditing: function() {
		var self = this;

		// We need to initialize the main snak before calling PARENT::startEditing,
		// since that triggers 'afterstartediting' which tries to set focus into
		// the main snak
		this._createMainSnak();
		return this._mainSnakSnakView.startEditing().then( function() {
			// PARENT::startEditing calls this.draw
			return PARENT.prototype.startEditing.call( self );
		} ).then( function() {
			self._rankSelector.startEditing();
			$.each( self._qualifiers.value(), function () {
				this.startEditing();
			} );
			self._startEditingReferences();
		} );
	},

	/**
	 * @protected
	 */
	_startEditingReferences: function() {
		$.each( this._referencesListview.value(), function ( key, referenceView ) {
			referenceView.startEditing();
		} );

		this._expandReferencesToggler();
	},

	/**
	 * @protected
	 */
	_expandReferencesToggler: function() {
		var toggler = this._$toggler.data( 'toggler' );
		if ( toggler.isCollapsed() ) {
			toggler.toggle();
		}
	},

	/**
	 * @inheritdoc
	 * @protected
	 */
	_afterStopEditing: function( dropValue ) {
		this._mainSnakSnakView.stopEditing( dropValue );
		this._stopEditingQualifiers( dropValue );
		this._rankSelector.stopEditing( dropValue );

		this._recreateReferences();

		return PARENT.prototype._afterStopEditing.call( this, dropValue );
	},

	/**
	 * @protected
	 */
	_recreateReferences: function() {
		// Normally, statementview would trigger a change event when references are removed and added
		this._ignoreReferencesListviewChanges = true;
		this._referencesListview.option( 'value', this.options.value
				? this.options.value.getReferences().toArray() : [] );
		this._ignoreReferencesListviewChanges = false;

		this._drawReferencesCounter();
	},

	/**
	 * @private
	 *
	 * @param {boolean} [dropValue=false]
	 */
	_stopEditingQualifiers: function( dropValue ) {
		var snaklistviews,
			i;

		snaklistviews = this._qualifiers.value();

		if ( snaklistviews.length ) {
			for ( i = 0; i < snaklistviews.length; i++ ) {
				snaklistviews[i].stopEditing( dropValue );

				if ( dropValue && !snaklistviews[i].value() ) {
					// Remove snaklistview from qualifier listview if no snakviews are left in
					// that snaklistview:
					this._qualifiers.removeItem( snaklistviews[i].element );
				}
			}
		}

		// Destroy and (if qualifiers still exist) re-create the qualifier listview in order to
		// re-group the qualifiers by their property. This will also send out the event to erase
		// the "add qualifier" toolbar.
		this._destroyQualifiersListView();

		var qualifiers = this.options.value ? this.options.value.getClaim().getQualifiers() : [];

		if ( qualifiers.length > 0 ) {
			// Refill the qualifier listview with the initial (or new initial) qualifiers:
			this._createQualifiersListview( qualifiers );
		}
	},

	/**
	 * @inheritdoc
	 * @private
	 *
	 * @return {Object} jQuery.Promise
	 * @return {Function} return.done
	 * @return {wikibase.datamodel.Statement} return.done.statement The saved statement.
	 * @return {Function} return.fail
	 * @return {wikibase.api.RepoApiError} return.fail.error
	 *
	 * @throws {Error} if unable to instantiate a `Statement` from the current view state.
	 */
	_save: function() {
		var self = this,
			guid = this.options.value
				? this.options.value.getClaim().getGuid()
				: this.options.guidGenerator.newGuid(),
			statement = this._instantiateStatement( guid );

		if ( !statement ) {
			throw new Error( 'Unable to instantiate Statement' );
		}

		return this.options.claimsChanger.setStatement( statement )
		.done( function( savedStatement ) {
			// Update model of represented Statement:
			self.options.value = savedStatement;
		} );
	},

	/**
	 * @inheritdoc
	 */
	isEmpty: function() {
		return false;
		// TODO: Supposed to do at least...
		// this._mainSnakSnakView.isEmpty(); (does not exist at the moment of writing)
	},

	/**
	 * @see jQuery.ui.EditableTemplatedWidget.isValid
	 * @return {boolean}
	 */
	isValid: function() {
		if ( !this.isInEditMode() ) {
			mw.log.warn( 'statementview::isValid should only be called in edit mode' );
			return true;
		}

		return this._mainSnakSnakView.isValid()
			// Main snak must be valid and serializable, which fails when the snaktype is missing.
			&& this._mainSnakSnakView.snak()
			&& this._rankSelector.isValid()
			&& ( !this._qualifiers || this._listViewIsValid( this._qualifiers ) )
			&& ( !this._referencesListview || this._listViewIsValid( this._referencesListview ) );
	},

	/**
	 * @param {jQuery.wikibase.listview} listview
	 * @return {boolean}
	 */
	_listViewIsValid: function( listview ) {
		var isValid = true;

		$.each( listview.value(), function ( key, view ) {
			isValid = view.isValid();
			return isValid;
		} );

		return isValid;
	},

	/**
	 * @inheritdoc
	 * @protected
	 *
	 * @throws {Error} when tyring to set `value` option.
	 */
	_setOption: function( key, value ) {
		if ( key === 'value' ) {
			throw new Error( 'Can not set value after initialization' );
		}

		var response = PARENT.prototype._setOption.apply( this, arguments );

		if ( key === 'disabled' ) {
			if ( this._mainSnakSnakView ) {
				this._mainSnakSnakView.option( key, value );
			}
			if ( this._qualifiers ) {
				this._qualifiers.option( key, value );
			}
			if ( this._rankSelector ) {
				this._rankSelector.option( key, value );
			}
			if ( this._referencesListview ) {
				this._referencesListview.option( key, value );
			}
		}

		return response;
	},

	/**
	 * @inheritdoc
	 */
	focus: function() {
		this._mainSnakSnakView.focus();
	}
} );

}( mediaWiki, wikibase, jQuery ) );
