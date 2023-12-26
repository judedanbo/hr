type Item = {
	data: Array<Object>;
	links: Array<Object>;
	current_page: number;
	total: number;
	from: number;
	to: number;
	last_page: number;
	per_page: number;
	path: string;
	first_page_url: string;
	last_page_url: string;
	next_page_url: string;
	prev_page_url: string;
};

export function useNavigation(item: Item) {
	let navigation = { ...item };
	delete navigation.data;
	return navigation;
}
