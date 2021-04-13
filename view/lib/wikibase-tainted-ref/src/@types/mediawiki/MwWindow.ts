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
	get( parameters: unknown, ajaxOptions?: unknown ): JQuery.Promise<any>;
	getEditToken(): JQuery.Promise<any>;
	getToken( type: string, assert?: string ): JQuery.Promise<any>;
	post( parameters: unknown, ajaxOptions?: unknown ): JQuery.Promise<any>;
	postWithEditToken( parameters: any, ajaxOptions?: unknown ): JQuery.Promise<any>;
	postWithToken( tokenType: string, parameters: unknown, ajaxOptions?: unknown ): JQuery.Promise<any>;
	login( username: string, password: string ): JQuery.Promise<any>;
}

export interface MediaWiki {
	loader: ResourceLoader;
	config: MwConfig;
	/** @see https://doc.wikimedia.org/mediawiki-core/master/js/#!/api/mw.log */
	log: MwLog;
	/** @see https://www.mediawiki.org/wiki/Manual:CORS */
	ForeignApi?: ForeignApiConstructor;
	/** @see https://doc.wikimedia.org/mediawiki-core/master/js/#!/api/mw.language */
	language: MwLanguage;
	/** @see https://doc.wikimedia.org/mediawiki-core/master/js/#!/api/mw.hook */
	hook: HookRegistry;
	/** @see https://doc.wikimedia.org/mediawiki-core/master/js/#!/api/mw.util */
	util: MwUtil;
	/** @see https://www.mediawiki.org/wiki/ResourceLoader/Core_modules#mw.track */
	track( topic: string, data: object|number|string ): void;
	/** @see https://doc.wikimedia.org/mediawiki-core/master/js/#!/api/mw-method-message */
	message( key: string ): Message;
}

export type HookRegistry = ( name: string ) => Hook;

export interface Hook {
	add( handler: Function ): Hook;
}

export interface MwUtil {
	getUrl( pageName: string ): string;
}

interface Message {
	text(): string;
}

/** @see: https://doc.wikimedia.org/mediawiki-core/master/js/#!/api/OO.ui.WindowInstance */
export interface WindowInstance {
	isClosed(): boolean;
}

export type WindowManagerConstructor = new() => WindowManager;
export interface WindowManager {
	addWindows( elements: OOElement[] ): void;
	openWindow( element: OOElement ): void;
	clearWindows(): JQuery.Promise<unknown, unknown, unknown>;
	destroy(): void;
	$element: JQuery;
}

export interface OOElement {
	initialize(): void;
}

/** @see: https://doc.wikimedia.org/mediawiki-core/master/js/#!/api/OO.ui.Dialog */
export type DialogConstructor = new( options: object ) => Dialog;
export interface Dialog extends OOElement {
	$body: JQuery;
	getBodyHeight(): number;
	close( data?: object ): WindowInstance;
	getManager(): WindowManager;
}

export type PanelLayoutConstructor = new( options: object ) => PanelLayout;
export interface PanelLayout {
	$element: JQuery;
}

export interface MwWindowOO {
	ui: {
		Dialog: DialogConstructor;
		PanelLayout: PanelLayoutConstructor;
		WindowManager: WindowManagerConstructor;
	};
	inheritClass( child: any, parent: any ): void; // takes "classes" as arguments
}

export interface UlsData {
	getDir( languageCode?: string ): 'ltr'|'rtl';
}

interface JQUls {
	data: UlsData;
}

export interface MWJQueryExtension {
	uls?: JQUls;
}

interface MwWindow extends Window {
}

export default MwWindow;
/* eslint-enable @typescript-eslint/no-explicit-any */
