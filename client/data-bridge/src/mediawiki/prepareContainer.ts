import {
	MwWindowOO,
	Dialog,
	PanelLayout,
} from '@/@types/mediawiki/MwWindow';

interface BridgeDialog extends Dialog {
	content: PanelLayout;
	$body: JQuery;
}

/**
 * Create a container element based on OO.ui.Dialog in which we can place our app
 *
 * @see https://doc.wikimedia.org/oojs-ui/master/js/#!/api/OO.ui.Dialog
 */
export default function prepareContainer( OO: MwWindowOO, $: JQueryStatic, id: string ): BridgeDialog {
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	const BridgeDialog: any = function ( this: BridgeDialog, config: object ) {
		BridgeDialog.parent.call( this, config );
	};
	OO.inheritClass( BridgeDialog, OO.ui.Dialog );
	BridgeDialog.static.name = 'data-bridge';
	BridgeDialog.static.escapable = true;
	BridgeDialog.prototype.initialize = function (): void {
		BridgeDialog.parent.prototype.initialize.call( this );
		this.content = new OO.ui.PanelLayout( { padded: false, expanded: false } );
		this.content.$element.append( `<div id="${id}"></div>` );
		this.$body.append( this.content.$element );
	};
	BridgeDialog.prototype.getBodyHeight = function (): number {
		return this.content.$element.outerHeight( true );
	};
	const bridgeDialog = new BridgeDialog( {
		size: 'medium',
	} );
	// Create and append a window manager, which opens and closes the window.
	const windowManager = new OO.ui.WindowManager();
	$( document.body ).append( windowManager.$element );
	windowManager.addWindows( [ bridgeDialog ] );
	// Open the window!
	windowManager.openWindow( bridgeDialog );

	return bridgeDialog;
}
