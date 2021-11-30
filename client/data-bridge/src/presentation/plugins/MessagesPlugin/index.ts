import MessagesRepository from '@/definitions/data-access/MessagesRepository';
import Messages from '@/presentation/plugins/MessagesPlugin/Messages';
import { App } from 'vue';

export default function MessagesPlugin( app: App, messages: MessagesRepository ): void {
	app.config.globalProperties.$messages = new Messages( messages );
}
