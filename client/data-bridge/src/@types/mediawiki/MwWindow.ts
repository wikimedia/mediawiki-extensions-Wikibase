import MwConfig from '@/@types/mediawiki/MwConfig';

interface ResourceLoader {
	using( module: string|string[] ): Promise<any>;
}

interface MwLog {
	deprecate( obj: object, key: string, val: any, msg?: string, logName?: string ): void;
	error( ...msg: any[] ): void;
	warn( ...msg: string[] ): void;
}

interface MediaWiki {
	loader: ResourceLoader;
	config: MwConfig;
	/** @see https://doc.wikimedia.org/mediawiki-core/master/js/#!/api/mw.log */
	log: MwLog;
}

export interface WindowManager {
	addWindows( elements: OOElement[] ): void;
	openWindow( element: OOElement ): void;
	$element: JQuery;
}

export interface OOElement {
	initialize(): void;
}

export interface Dialog extends OOElement {
	$body: JQuery;
	getBodyHeight(): number;
}

export interface PanelLayout {
	$element: JQuery;
}

export interface MwWindowOO {
	ui: {
		Dialog: new( options: object ) => Dialog;
		PanelLayout: new( options: object ) => PanelLayout;
		WindowManager: new() => WindowManager;
	};
	inheritClass( child: any, parent: any ): void; // takes "classes" as arguments
}

interface MwWindow extends Window {
	mw: MediaWiki;
	OO: MwWindowOO;
	$: JQueryStatic;
}

export default MwWindow;
/* eslint-enable @typescript-eslint/no-explicit-any */
