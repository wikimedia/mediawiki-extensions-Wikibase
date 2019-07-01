interface MwConfig {
	get( key: string ): any;
}

interface ResourceLoader {
	using( module: string|string[] ): Promise<any>;
}

interface MediaWiki {
	loader: ResourceLoader;
	config: MwConfig;
}

interface MwWindow extends Window {
	mw: MediaWiki;
}

export default MwWindow;
