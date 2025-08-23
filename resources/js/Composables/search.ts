import { router } from "@inertiajs/vue3";
import { ref } from "vue";

const data = ref(null);
export function useSearch(value: string, url: string | URL) {
	data.value = router.get(
		url,
		{ search: value },
		{ preserveState: true, replace: true },
	);
	return {
		data,
	};
}
