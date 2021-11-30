import LanguageInfoRepository from '@/definitions/data-access/LanguageInfoRepository';
import { App } from 'vue';

export default function InLanguagePlugin( app: App, resolver: LanguageInfoRepository ): void {
	app.config.globalProperties.$inLanguage = ( mwLangCode: string ) => {
		if ( !mwLangCode ) {
			return {};
		}
		const language = resolver.resolve( mwLangCode );
		return {
			lang: language.code,
			dir: language.directionality,
		};
	};
}
