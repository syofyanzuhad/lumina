import { ref, computed  } from 'vue';
import type {Ref} from 'vue';

export interface DailyChartItem {
    date: string;
    pageviews: number;
    visitors: number;
}

export function useAnalyticsChart(dailyPageviews: Ref<DailyChartItem[] | undefined>) {
    const hoveredDay = ref<DailyChartItem | null>(null);
    const showViews = ref(true);
    const showVisitors = ref(false);

    const toggleViews = () => {
        showViews.value = !showViews.value;
    };

    const toggleVisitors = () => {
        showVisitors.value = !showVisitors.value;
    };

    const viewsMax = computed(() => {
        const list = dailyPageviews.value;

        if (!list || !list.length) {
return 1;
}

        const m = Math.max(...list.map((d) => d.pageviews));

        return m > 0 ? m : 1;
    });

    const visitorsMax = computed(() => {
        const list = dailyPageviews.value;

        if (!list || !list.length) {
return 1;
}

        const m = Math.max(...list.map((d) => d.visitors));

        return m > 0 ? m : 1;
    });

    const maxDaily = computed(() => {
        const vals: number[] = [];

        if (showViews.value) {
vals.push(viewsMax.value);
}

        if (showVisitors.value) {
vals.push(visitorsMax.value);
}

        if (!vals.length) {
return 1;
}

        return Math.max(...vals);
    });

    return {
        hoveredDay,
        showViews,
        showVisitors,
        toggleViews,
        toggleVisitors,
        viewsMax,
        visitorsMax,
        maxDaily,
    };
}
