import { DirectiveBinding } from 'vue/types/options';
import { VNode } from 'vue/types/vnode';
import LanguageInfoRepository from '@/definitions/data-access/LanguageInfoRepository';

export default ( resolver: LanguageInfoRepository ) => {
	return ( el: HTMLElement, binding: DirectiveBinding, _vnode: VNode ) => {
		if ( !binding.value ) {
			return;
		}

		const language = resolver.resolve( binding.value );
		el.setAttribute( 'lang', language.code );
		el.setAttribute( 'dir', language.directionality );
	};
};
