import { Inertia } from "@inertiajs/inertia";
import { ref } from "vue";

const data = ref(null);
export function useSearch(value: string, url: string | URL) {
	data.value = Inertia.get(
		url,
		{ search: value },
		{ preserveState: true, replace: true },
	);
	return {
		data,
	};
}
