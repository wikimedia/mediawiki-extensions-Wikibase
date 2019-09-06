import { DirectiveBinding } from 'vue/types/options';
import { VNode } from 'vue/types/vnode';
import DirectionalityRepository from '@/definitions/data-access/DirectionalityRepository';

export default ( resolver: DirectionalityRepository ) => {
	return ( el: HTMLElement, binding: DirectiveBinding, _vnode: VNode ) => {
		if ( !binding.value ) {
			return;
		}

		const languageCode: string = binding.value;
		const directionality: string = resolver.resolve( languageCode );
		el.setAttribute( 'lang', languageCode );
		el.setAttribute( 'dir', directionality );
	};
};
