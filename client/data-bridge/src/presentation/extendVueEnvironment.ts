import Vue from 'vue';
import inlanguage from './directives/inlanguage';
import MessagesPlugin from './plugins/MessagesPlugin';
import LanguageInfoRepository from '@/definitions/data-access/LanguageInfoRepository';
import MessagesRepository from '@/definitions/data-access/MessagesRepository';

export default function extendVueEnvironment(
	languageInfoRepo: LanguageInfoRepository,
	messageRepo: MessagesRepository,
): void {
	Vue.directive( 'inlanguage', inlanguage( languageInfoRepo ) );
	Vue.use( MessagesPlugin, messageRepo );
}
