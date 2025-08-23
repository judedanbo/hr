<script setup>
import { Link } from "@inertiajs/vue3";
import DeleteAddressModal from "./DeleteAddressModal.vue";
import AddAddressModal from "./AddAddressModal.vue";
import { format, differenceInYears } from "date-fns";

import {
	MagnifyingGlassIcon,
	HomeModernIcon,
	AtSymbolIcon,
	PhoneIcon,
} from "@heroicons/vue/24/outline";
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
defineProps({
	person: Object,
	address: Object,
});

let addAddress = () => {
	showAddAddressModal.value = true;
};
let showDeleteAddressModal = ref(false);
let addressToDelete = ref(null);
let showAddAddressModal = ref(false);
let deleteAddress = (id) => {
	addressToDelete.value = id;
	showDeleteAddressModal.value = true;
};
let editAddress = (id) => {};

const formattedDob = (dateString) => {
	const date = new Date(dateString);
	return format(date, "dd MMMM, yyyy");
};

let getAge = (dateString) => {
	const date = new Date(dateString);
	return differenceInYears(new Date(), date);
};

let showPerson = (id) => {
	router.get(route("person.show", { person: id }));
};
</script>
<template>
	<div class="overflow-hidden bg-white shadow sm:rounded-lg w-full mx-auto">
		<div class="px-4 pt-6 sm:px-6">
			<h3 class="text-lg font-medium leading-6 text-gray-900">Address</h3>
		</div>

		<div class="overflow-x-auto relative shadow-md sm:rounded-lg">
			<div class="flex justify-end items-center px-4 bg-white dark:bg-gray-800">
				<button
					type="button"
					class="text-white bg-green-700 hover:bg-green-800 focus:outline-none focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center mr-2 mb-2 dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800 inline-flex items-center"
					@click.stop.prevent="addAddress"
				>
					<HomeModernIcon class="w-5 h-5 mr-2" />
					Add new address
				</button>
			</div>
			<table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
				<thead
					class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400"
				></thead>
				<tbody>
					<tr
						v-if="address"
						class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
					>
						<td
							scope="row"
							class="py-4 px-6 text-gray-900 whitespace-nowrap dark:text-white hover:cursor-pointer"
						>
							<p>{{ address.address_line_1 }}</p>
							<p>{{ address.address_line_2 }}</p>
							<p>
								{{ address.city }},
								{{ address.region }}
							</p>
							<p>{{ address.country }}</p>
							<p>{{ address.post_code }}</p>
						</td>

						<td class="py-4 px-6 text-right space-x-3">
							<!-- Modal toggle -->
							<button
								type="button"
								class="font-medium text-green-600 dark:text-green-500 hover:underline"
								@click.prevent="addAddress"
							>
								Edit
							</button>
							<button
								type="button"
								class="font-medium text-red-600 dark:text-red-500 hover:underline"
								@click.prevent="deleteAddress(address.id)"
							>
								Delete
							</button>
						</td>
					</tr>
					<tr v-else>
						<td
							class="text-center py-4 bg-gray-200 text-white text-lg tracking-wide font-bold"
						>
							No address provided
						</td>
					</tr>
				</tbody>
			</table>
			<DeleteAddressModal
				:address="addressToDelete"
				:person="person.id"
				:is-visible="showDeleteAddressModal"
				@closeModal="showDeleteAddressModal = false"
			/>
			<AddAddressModal
				:person_id="person.id"
				:is-visible="showAddAddressModal"
				@closeModal="showAddAddressModal = false"
			/>
		</div>
	</div>
</template>
