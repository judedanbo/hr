import { usePage } from "@inertiajs/vue3";
import { format, parseISO, isValid } from "date-fns";
import { phpToDateFns } from "@/composables/phpToDateFns";

export { phpToDateFns };

/**
 * Returns a `formatDate(value)` bound to the app's configured date_format.
 * Accepts an ISO date string or a Date; returns '' for null/invalid input.
 */
export function useDateFormat() {
	const page = usePage();

	const formatDate = (value) => {
		if (!value) {
			return "";
		}
		const date = value instanceof Date ? value : parseISO(String(value));
		if (!isValid(date)) {
			return "";
		}
		return format(date, phpToDateFns(page.props.app?.date_format ?? "d M Y"));
	};

	return { formatDate };
}
