import MessagesRepository from '@/definitions/data-access/MessagesRepository';
import Messages from '@/presentation/plugins/MessagesPlugin/Messages';
import _Vue from 'vue';

export default function MessagesPlugin( Vue: typeof _Vue, messages: MessagesRepository ): void {
	Vue.prototype.$messages = new Messages( messages );
}
