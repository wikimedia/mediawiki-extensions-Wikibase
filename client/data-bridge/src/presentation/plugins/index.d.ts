import Messages from '@/presentation/plugins/MessagesPlugin/Messages';
import Vue from 'vue';

declare module 'vue/types/vue' {

	interface Vue {
		$messages: Messages;
	}
}
