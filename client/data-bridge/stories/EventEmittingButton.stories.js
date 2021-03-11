import EventEmittingButton from '@/presentation/components/EventEmittingButton.vue';

export default {
	title: 'EventEmittingButton',
	component: EventEmittingButton,
};

export function primaryProgressiveL() {
	return {
		components: { EventEmittingButton },
		template:
			`<EventEmittingButton
				type="primaryProgressive"
				size="L"
				message="primaryProgressive"
			/>`,
	};
}

export function primaryProgressiveAsLink() {
	return {
		components: { EventEmittingButton },
		template:
			`<EventEmittingButton
				type="primaryProgressive"
				size="L"
				message="primaryProgressive"
				href="https://www.mediawiki.org/wiki/Wikidata_Bridge"
				:preventDefault="false"
			/>`,
	};
}

export function primaryProgressiveAsLinkOpeningInNewTab() {
	return {
		components: { EventEmittingButton },
		template:
			`<EventEmittingButton
				type="primaryProgressive"
				size="L"
				message="primaryProgressive"
				href="https://www.mediawiki.org/wiki/Wikidata_Bridge"
				:newTab="true"
				:preventDefault="false"
			/>`,
	};
}

export function squaryPrimaryProgressive() {
	return {
		components: { EventEmittingButton },
		template:
			`<EventEmittingButton
				type="primaryProgressive"
				size="L"
				:squary="true"
				message="squary primaryProgressive"
				/>`,
	};
}

export function primaryProgressiveM() {
	return {
		components: { EventEmittingButton },
		template:
			`<EventEmittingButton
				type="primaryProgressive"
				size="M"
				message="primaryProgressive M"
			/>`,
	};
}

export function primaryProgressiveXL() {
	return {
		components: { EventEmittingButton },
		template: `
			<EventEmittingButton
				type="primaryProgressive"
				size="XL"
				message="primaryProgressive XL"
			/>`,
	};
}

export function primaryProgressiveMFullWidth() {
	return {
		components: { EventEmittingButton },
		template:
			`<div style="max-width: 25em; padding: 2em; border: 1px solid black;">
				<EventEmittingButton
					type="primaryProgressive"
					size="M"
					message="primaryProgressive M"
					style="width: 100%"
				/>
				</div>`,
	};
}

export function closeM() {
	return {
		components: { EventEmittingButton },
		template:
			`<EventEmittingButton
				type="close"
				size="M"
				message="close"
			/>`,
	};
}

export function closeL() {
	return {
		components: { EventEmittingButton },
		template:
			`<EventEmittingButton
				type="close"
				size="L"
				message="close"
			/>`,
	};
}

export function closeXL() {
	return {
		components: { EventEmittingButton },
		template:
			`<EventEmittingButton
				type="close"
				size="XL"
				message="close"
			/>`,
	};
}

export function closeSquary() {
	return {
		components: { EventEmittingButton },
		template:
			`<EventEmittingButton
				type="close"
				size="L"
				:squary="true"
				message="close"
				/>`,
	};
}

export function primaryProgressiveDisabled() {
	return {
		components: { EventEmittingButton },
		template:
			`<EventEmittingButton
				type="primaryProgressive"
				size="L"
				message="disabled primaryProgressive"
				:disabled="true"
				/>`,
	};
}

export function closeDisabled() {
	return {
		components: { EventEmittingButton },
		template:
			`<EventEmittingButton
				type="close"
				size="L"
				message="disabled close"
				:disabled="true"
				/>`,
	};
}

export function neutralM() {
	return {
		components: { EventEmittingButton },
		template:
			`<EventEmittingButton
				type="neutral"
				size="M"
				message="Go back"
			/>`,
	};
}

export function backL() {
	return {
		components: { EventEmittingButton },
		template:
			`<EventEmittingButton
				type="back"
				size="L"
				message="back"
			/>`,
	};
}

export function backRTL() {
	return {
		components: { EventEmittingButton },
		template:
			`<EventEmittingButton
				style="transform: scaleX( -1 );"
				type="back"
				size="L"
				message="back"
				/>`,
	};
}

export function linkM() {
	return {
		components: { EventEmittingButton },
		template:
			`<EventEmittingButton
				type="link"
				size="M"
				message="Keep editing"
			/>`,
	};
}

export function linkMDisabled() {
	return {
		components: { EventEmittingButton },
		template:
			`<EventEmittingButton
				type="link"
				size="M"
				message="Keep editing"
				:disabled="true"
			/>`,
	};
}
