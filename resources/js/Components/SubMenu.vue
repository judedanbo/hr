<script setup>
import { Menu, MenuButton, MenuItem, MenuItems } from "@headlessui/vue";
import {
	EllipsisVerticalIcon,
	PlusIcon,
	EllipsisHorizontalIcon,
} from "@heroicons/vue/20/solid";

defineProps({
	items: { type: Array, default: () => [] },
	canEdit: { type: Boolean, default: false },
	canDelete: { type: Boolean, default: false },
	canView: { type: Boolean, default: false },
	canApprove: { type: Boolean, default: false },
	canRevoke: { type: Boolean, default: false },
	canChangeUserPassword: { type: Boolean, default: false },
	canContacts: { type: Boolean, default: false },
	canAttach: { type: Boolean, default: false },
	canAddStaffQualification: { type: Boolean, default: false },
});
const emit = defineEmits(["itemClicked"]);
</script>
<template>
	<Menu as="div" class="relative">
		<MenuButton
			class="ml-3 block py-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 group"
		>
			<span class="sr-only">Menu</span>
			<EllipsisVerticalIcon
				class="h-5 w-5 text-gray-500 dark:text-gray-400 group-hover:text-green-600 dark:group-hover:text-gray-200"
				aria-hidden="true"
			/>
		</MenuButton>

		<transition
			enter-active-class="transition ease-out duration-100"
			enter-from-class="transform opacity-0 scale-95"
			enter-to-class="transform opacity-100 scale-100"
			leave-active-class="transition ease-in duration-75"
			leave-from-class="transform opacity-100 scale-100"
			leave-to-class="transform opacity-0 scale-95"
		>
			<MenuItems
				class="absolute right-0 top-full z-50 mt-1 w-40 origin-top-right rounded-lg bg-white dark:bg-gray-800 py-1 shadow-lg ring-1 ring-gray-900/5 dark:ring-gray-700 focus:outline-none"
			>
				<template v-for="item in items" :key="item">
					<MenuItem
						v-if="
							(item === 'Edit' && canEdit) ||
							(item === 'Revoke' && canEdit) ||
							(item === 'Delete' && canDelete) ||
							(item === 'Open' && canView) ||
							(item === 'Approve' && canApprove) ||
							(item === 'Revoke' && canRevoke) ||
							(item === 'Reset Password' && canChangeUserPassword) ||
							(item === 'Contacts' && canContacts) ||
							(item === 'Attach' && canAttach)
						"
						v-slot="{ active }"
						@click="emit('itemClicked', item)"
					>
						<button
							type="button"
							:class="[
								active ? 'bg-gray-100 dark:bg-gray-700' : '',
								'block w-full py-1.5 px-4 text-left text-sm leading-6 text-gray-700 dark:text-gray-200',
							]"
						>
							{{ item }}
						</button>
					</MenuItem>
				</template>
			</MenuItems>
		</transition>
	</Menu>
</template>
