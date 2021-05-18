const Page = require( 'wdio-mediawiki/Page' ),
	Util = require( 'wdio-mediawiki/Util' ),
	WarningAnonymousEdit = require( './WarningAnonymousEdit' );

class DataBridgePage extends Page {
	static get ARTICLE_ELEMENTS() {
		return {
			INFOBOX: '.mw-body-content .infobox',
		};
	}

	static get EDIT_LINK_ANCHOR() {
		return 'Edit this on Wikidata';
	}

	static get OOUI() {
		return '.oo-ui-dialog';
	}

	static get ROOT() {
		return '#data-bridge-app';
	}

	static get HEADER_ELEMENTS() {
		const headerClass = '.wb-ui-processdialog-header';
		return {
			SAVE: `${headerClass} .wb-ui-event-emitting-button--primaryProgressive`,
			CLOSE: `${headerClass} .wb-ui-event-emitting-button--close`,
			BACK: `${headerClass} .wb-ui-event-emitting-button--back`,
		};
	}

	static get ROOT_SWITCH() {
		return {
			ERROR: '.wb-db-error',
			BRIDGE: '.wb-db-bridge',
			THANKYOU: '.wb-db-thankyou',
		};
	}

	static get ERROR_TYPES() {
		return {
			UNKNOWN: '.wb-db-error-unknown',
			PERMISSION: '.wb-db-error-permission',
			SAVING: '.wb-db-error-saving',
		};
	}

	static get STRING_VALUE() {
		return '.wb-db-string-value .wb-db-string-value__input';
	}

	static get PROPERTY_LABEL() {
		return '.wb-db-property-label';
	}

	static get LICENSE_POPUP() {
		return '.wb-db-license';
	}

	static get LICENSE_CLOSE_BUTTON() {
		return `${DataBridgePage.LICENSE_POPUP} .wb-db-license__button a`;
	}

	static get REFERENCES_SECTION() {
		return '.wb-db-references';
	}

	static get REFERENCE() {
		return '.wb-db-references__listItem';
	}

	static get EDIT_DECISION_SECTION() {
		return '.wb-db-edit-decision';
	}

	static get EDIT_DECISION_INPUT() {
		return 'input[name=editDecision]';
	}

	static get PERMISSION_ERROR_CONTAINER() {
		return '.wb-db-error-permission-info';
	}

	static get THANKYOU_BUTTON() {
		return '.wb-db-thankyou__button';
	}

	static get ERROR_UNKNOWN_BUTTON_RELAUNCH() {
		return '.wb-db-error-unknown__relaunch';
	}

	static get RETRY_SAVE_BUTTON() {
		return '.wb-db-error-saving__retry';
	}

	static get ERROR_SAVING_BACK_BUTTON() {
		// only visible on desktop (window width larger than breakpoint)
		return '.wb-db-error-saving__back';
	}

	static get LOADING_BAR() {
		return '.wb-db-load__bar';
	}

	getDummyTitle() {
		return Util.getTestString( 'Talk:Data-bridge-test-page-' );
	}

	/**
	 * @param {Array.<{label: String, entityId: String, propertyId: String, editFlow: String}>} rows
	 * @return {string} wikitext
	 */
	createInfoboxWikitext( rows ) {
		return '{|class="wikitable infobox"' +
			rows.map( ( row ) => `
|-
| ${row.label}
| {{#statements:${row.propertyId}|from=${row.entityId}}}&nbsp;<span data-bridge-edit-flow="${row.editFlow}">[https://example.org/wiki/Item:${row.entityId}?uselang=en#${row.propertyId} ${DataBridgePage.EDIT_LINK_ANCHOR}]</span>` ).join( '' ) +
			'\n|}';
	}

	open( title ) {
		super.openTitle( title );
		Util.waitForModuleState( 'wikibase.client.data-bridge.app', 'ready', browser.config.waitforTimeout );
	}

	openAppOnPage( title ) {
		this.open( title );
		this.launchApp();
	}

	launchApp() {
		this.overloadedLink.click();
		this.app.waitForDisplayed( { timeout: browser.config.nonApiTimeout } );
		WarningAnonymousEdit.dismiss();
	}

	get overloadedLink() {
		return $( `a=${DataBridgePage.EDIT_LINK_ANCHOR}` );
	}

	get dialog() {
		return $( '.oo-ui-dialog' );
	}

	get app() {
		return $( '#data-bridge-app' );
	}

	get saveButton() {
		return $(
			`${DataBridgePage.OOUI} ${DataBridgePage.ROOT} ${DataBridgePage.HEADER_ELEMENTS.SAVE}`
		);
	}

	get licensePopup() {
		return $(
			`${DataBridgePage.OOUI} ${DataBridgePage.ROOT} ${DataBridgePage.LICENSE_POPUP}`
		);
	}

	get licenseCloseButton() {
		return $(
			`${DataBridgePage.OOUI} ${DataBridgePage.ROOT} ${DataBridgePage.LICENSE_CLOSE_BUTTON}`
		);
	}

	get closeButton() {
		return $(
			`${DataBridgePage.OOUI} ${DataBridgePage.ROOT} ${DataBridgePage.HEADER_ELEMENTS.CLOSE}`
		);
	}

	get headerBackButton() {
		return $(
			`${DataBridgePage.OOUI} ${DataBridgePage.ROOT} ${DataBridgePage.HEADER_ELEMENTS.BACK}`
		);
	}

	get retrySaveButton() {
		return $(
			`${DataBridgePage.OOUI} ${DataBridgePage.ROOT} ${DataBridgePage.RETRY_SAVE_BUTTON}`
		);
	}

	get errorSavingBackButton() {
		return $(
			`${DataBridgePage.OOUI} ${DataBridgePage.ROOT} ${DataBridgePage.ERROR_SAVING_BACK_BUTTON}`
		);
	}

	get loadingBar() {
		return $(
			`${DataBridgePage.OOUI} ${DataBridgePage.ROOT} ${DataBridgePage.LOADING_BAR}`
		);
	}

	get error() {
		return $(
			`${DataBridgePage.OOUI} ${DataBridgePage.ROOT} ${DataBridgePage.ROOT_SWITCH.ERROR}`
		);
	}

	showsErrorUnknown() {
		return this.error.$( DataBridgePage.ERROR_TYPES.UNKNOWN ).isDisplayed();
	}

	showsErrorPermission() {
		return this.error.$( DataBridgePage.ERROR_TYPES.PERMISSION ).isDisplayed();
	}

	get permissionErrors() {
		return this.error.$$( DataBridgePage.PERMISSION_ERROR_CONTAINER );
	}

	showsErrorSaving() {
		return this.error.$( DataBridgePage.ERROR_TYPES.SAVING ).isDisplayed();
	}

	get bridge() {
		return $(
			`${DataBridgePage.OOUI} ${DataBridgePage.ROOT} ${DataBridgePage.ROOT_SWITCH.BRIDGE}`
		);
	}

	get value() {
		return $(
			`${DataBridgePage.OOUI} ${DataBridgePage.ROOT} ${DataBridgePage.ROOT_SWITCH.BRIDGE} ${DataBridgePage.STRING_VALUE}`
		);
	}

	/**
	 * @param {number} n Row of the infobox to retrieve the value from (1-indexed)
	 * @returns {jQuery|HTMLElement}
	 */
	nthInfoboxValue( n ) {
		return $(
			`${DataBridgePage.ARTICLE_ELEMENTS.INFOBOX} tr:nth-child( ${n} ) td:nth-child( 2 ) span:nth-child( 1 )`
		);
	}

	get propertyLabel() {
		return $(
			`${DataBridgePage.OOUI} ${DataBridgePage.ROOT} ${DataBridgePage.ROOT_SWITCH.BRIDGE} ${DataBridgePage.PROPERTY_LABEL}`
		);
	}

	/**
	 * @param {number} n Reference number (1-indexed)
	 */
	nthReference( n ) {
		return $(
			`${DataBridgePage.OOUI} ${DataBridgePage.ROOT} ${DataBridgePage.ROOT_SWITCH.BRIDGE}
				${DataBridgePage.REFERENCES_SECTION} ${DataBridgePage.REFERENCE}:nth-child( ${n} )`
		);
	}

	editDecision( value ) {
		return $(
			`${DataBridgePage.OOUI} ${DataBridgePage.ROOT} ${DataBridgePage.ROOT_SWITCH.BRIDGE}
				${DataBridgePage.EDIT_DECISION_SECTION} ${DataBridgePage.EDIT_DECISION_INPUT}[value=${value}]`
		);
	}

	get thankYouScreen() {
		return $(
			`${DataBridgePage.OOUI} ${DataBridgePage.ROOT} ${DataBridgePage.ROOT_SWITCH.THANKYOU}`
		);
	}

	get thankYouButton() {
		return this.thankYouScreen.$( DataBridgePage.THANKYOU_BUTTON );
	}

	get errorUnknownRelaunch() {
		return $(
			`${DataBridgePage.OOUI} ${DataBridgePage.ROOT} ${DataBridgePage.ERROR_TYPES.UNKNOWN} ${DataBridgePage.ERROR_UNKNOWN_BUTTON_RELAUNCH}`
		);
	}

	setMobileWindowSize( mobile = true ) {
		const DESKTOP_WIDTH = 800,
			DESKTOP_HEIGHT = 600,
			MOBILE_WIDTH = 300,
			MOBILE_HEIGHT = 740,
			targetWidth = mobile ? MOBILE_WIDTH : DESKTOP_WIDTH,
			targetHeight = mobile ? MOBILE_HEIGHT : DESKTOP_HEIGHT,
			browserWidth = browser.getWindowSize().width;

		if (
			mobile === false && browserWidth >= targetWidth
			||
			mobile && browserWidth <= targetWidth
		) {
			return; // don't change size if window has right dimensions already
		}

		browser.emulateDevice( {
			viewport: {
				width: targetWidth, // <number> page width in pixels.
				height: targetHeight, // <number> page height in pixels.
			},
			userAgent: `acting like a ${mobile ? 'narrow' : 'wide'} viewport`,
		} );
		// FIXME: reenable if tests become flaky and we still need that pause:
		// browser.pause( 1000 ); // wait for resize animations to complete
	}
}

module.exports = new DataBridgePage();
