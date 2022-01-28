import MessageOptions from '@/@types/MessageOptions';
import { App } from 'vue';

export default function Message(
	app: App,
	options: MessageOptions,
): void {
	app.config.globalProperties.$message = ( key: string ): string => {
		return options.messageToTextFunction( key );
	};
}
