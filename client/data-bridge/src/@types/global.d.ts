import {
	MediaWiki,
	MwWindowOO,
	MWJQueryExtension,
} from '@/@types/mediawiki/MwWindow';

declare global {
	interface Window {
		mw: MediaWiki;
		OO: MwWindowOO;
		$: JQueryStatic & MWJQueryExtension;
	}
}
