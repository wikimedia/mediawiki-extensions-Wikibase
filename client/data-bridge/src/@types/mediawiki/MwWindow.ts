/* eslint-disable @typescript-eslint/no-explicit-any */
import MwConfig from '@/@types/mediawiki/MwConfig';

interface ResourceLoader {
	using( module: string|string[] ): Promise<any>;
}

export interface MwLanguage {
	bcp47( languageTag: string ): string;
}

interface MwLog {
	deprecate( obj: object, key: string, val: any, msg?: string, logName?: string ): void;
	error( ...msg: any[] ): void;
	warn( ...msg: string[] ): void;
}

/** see: https://doc.wikimedia.org/mediawiki-core/master/js/#!/api/mw.Api-property-defaultOptions */
interface ApiOptions {
	parameters: any;
	ajax: JQuery.jqXHR;
	useUse: boolean;
}

/** see: https://doc.wikimedia.org/mediawiki-core/master/js/#!/api/mw.ForeignApi */
export type ForeignApiConstructor = new( url: string, options?: ApiOptions ) => ForeignApi;
export interface ForeignApi {
	get: ( parameters: unknown, ajaxOptions?: unknown ) => JQuery.Promise<any>;
	getEditToken(): JQuery.Promise<any>;
	getToken( type: string, assert?: string ): JQuery.Promise<any>;
	post( parameters: unknown, ajaxOptions?: unknown ): JQuery.Promise<any>;
	postWithEditToken( parameters: any, ajaxOptions?: unknown ): JQuery.Promise<any>;
	postWithToken( tokenType: string, parameters: unknown, ajaxOptions?: unknown ): JQuery.Promise<any>;
	login( username: string, password: string ): JQuery.Promise<any>;
}

interface MediaWiki {
	loader: ResourceLoader;
	config: MwConfig;
	/** @see https://doc.wikimedia.org/mediawiki-core/master/js/#!/api/mw.log */
	log: MwLog;
	/** @see https://www.mediawiki.org/wiki/Manual:CORS */
	ForeignApi?: ForeignApiConstructor;
	/** @see https://doc.wikimedia.org/mediawiki-core/master/js/#!/api/mw.language */
	language: MwLanguage;
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

export interface UlsData {
	getDir: ( languageCode?: string ) => 'ltr'|'rtl';
}

interface JQUls {
	data: UlsData;
}

interface MWJQueryExtension {
	uls?: JQUls;
}

interface MwWindow extends Window {
	mw: MediaWiki;
	OO: MwWindowOO;
	$: JQueryStatic&MWJQueryExtension;
}

export default MwWindow;
/* eslint-enable @typescript-eslint/no-explicit-any */
