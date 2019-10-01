/* eslint-disable @typescript-eslint/no-explicit-any */
import MwConfig from '@/@types/mediawiki/MwConfig';

export interface MwMessage {
	text(): string;
}
export type MwMessages = ( key: string, ...params: string[] ) => MwMessage;

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

interface MediaWiki {
	loader: ResourceLoader;
	/** @see https://doc.wikimedia.org/mediawiki-core/master/js/#!/api/mw.Message */
	message: MwMessages;
	config: MwConfig;
	/** @see https://doc.wikimedia.org/mediawiki-core/master/js/#!/api/mw.log */
	log: MwLog;
	/** @see https://www.mediawiki.org/wiki/Manual:CORS */
	ForeignApi?: ForeignApiConstructor;
	/** @see https://doc.wikimedia.org/mediawiki-core/master/js/#!/api/mw.language */
	language: MwLanguage;
}

interface Events {
	[ name: string ]: any[];
}
type EventListener<A extends any[]> = ( ...args: A ) => void;

/** https://doc.wikimedia.org/oojs/master/OO.EventEmitter.html */
export interface OOEventEmitter<E extends Events> {
	emit<K extends keyof E>( event: K, ...args: E[K] ): boolean;
	emitThrow<K extends keyof E>( event: K, ...args: E[K] ): boolean;
	off<K extends keyof E>( event: K, listener: EventListener<E[K]> ): this;
	on<K extends keyof E>( event: K, listener: EventListener<E[K]> ): this;
	once<K extends keyof E>( event: K, listener: EventListener<E[K]> ): this;
}

/** @see: https://doc.wikimedia.org/mediawiki-core/master/js/#!/api/OO.ui.Window */
export interface OOUIWindow extends OOElement {}

/** @see: https://doc.wikimedia.org/mediawiki-core/master/js/#!/api/OO.ui.WindowInstance */
export interface WindowInstance {
	isClosed(): boolean;
}

interface WindowManagerEvents extends Events {
	closing: [OOUIWindow, JQuery.Promise<unknown>, object];
	opening: [OOUIWindow, JQuery.Promise<unknown>, object];
	resize: [OOUIWindow];
}

export type WindowManagerConstructor = new() => WindowManager;
export interface WindowManager extends OOEventEmitter<WindowManagerEvents> {
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
