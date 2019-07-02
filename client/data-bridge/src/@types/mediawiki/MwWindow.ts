interface MwConfig {
	get( key: string ): any;
}

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

interface MwWindow extends Window {
	mw: MediaWiki;
}

export default MwWindow;
