import LanguageInfoRepository from '@/definitions/data-access/LanguageInfoRepository';
import _Vue from 'vue';

export default function InLanguagePlugin( Vue: typeof _Vue, resolver: LanguageInfoRepository ): void {
	Vue.prototype.$inLanguage = ( mwLangCode: string ) => {
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
