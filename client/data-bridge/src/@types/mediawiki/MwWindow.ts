interface ResourceLoader {
	using( module: string|string[] ): Promise<any>;
}

interface MediaWiki {
	loader: ResourceLoader;
}

interface MwWindow extends Window {
	mw: MediaWiki;
}

export default MwWindow;
