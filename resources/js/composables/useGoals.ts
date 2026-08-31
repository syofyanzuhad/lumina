import { ref } from 'vue';
import { toast } from 'vue-sonner';

export type Goal = {
    id: number;
    name: string;
    target_type: 'path' | 'custom_event';
    target_value: string;
};

export function useGoals(siteId: number) {
    const goals = ref<Goal[]>([]);
    const isLoading = ref(true);
    const isError = ref(false);
    const isModalOpen = ref(false);
    const isDeleteModalOpen = ref(false);
    const editingGoal = ref<Goal | null>(null);
    const goalToDelete = ref<Goal | null>(null);

    const form = ref<{
        name: string;
        target_type: 'path' | 'custom_event';
        target_value: string;
    }>({
        name: '',
        target_type: 'path',
        target_value: '',
    });

    const fetchApi = async (method: string, url: string, data?: any) => {
        const token = decodeURIComponent(
            document.cookie
                .split('; ')
                .find((row) => row.startsWith('XSRF-TOKEN='))
                ?.split('=')[1] || '',
        );
        const res = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-XSRF-TOKEN': token,
            },
            body: data ? JSON.stringify(data) : undefined,
        });

        if (!res.ok) {
            throw new Error('API Error');
        }

        if (res.status === 204) {
            return null;
        }

        return await res.json();
    };

    const fetchGoals = async () => {
        isLoading.value = true;
        isError.value = false;

        try {
            const data = await fetchApi('GET', `/sites/${siteId}/goals`);
            goals.value = data;
        } catch {
            isError.value = true;
            toast.error('Failed to load goals. Please try again.');
        } finally {
            isLoading.value = false;
        }
    };

    const openCreate = () => {
        editingGoal.value = null;
        form.value = { name: '', target_type: 'path', target_value: '' };
        isModalOpen.value = true;
    };

    const openEdit = (goal: Goal) => {
        editingGoal.value = goal;
        form.value = {
            name: goal.name,
            target_type: goal.target_type,
            target_value: goal.target_value,
        };
        isModalOpen.value = true;
    };

    const confirmDelete = (goal: Goal) => {
        goalToDelete.value = goal;
        isDeleteModalOpen.value = true;
    };

    const saveGoal = async () => {
        try {
            if (editingGoal.value) {
                await fetchApi(
                    'PUT',
                    `/sites/${siteId}/goals/${editingGoal.value.id}`,
                    form.value,
                );
                toast.success('Goal updated successfully');
            } else {
                await fetchApi('POST', `/sites/${siteId}/goals`, form.value);
                toast.success('Goal created successfully');
            }

            isModalOpen.value = false;
            fetchGoals();
        } catch {
            toast.error('Failed to save goal');
        }
    };

    const deleteGoal = async () => {
        if (!goalToDelete.value) {
            return;
        }

        try {
            await fetchApi(
                'DELETE',
                `/sites/${siteId}/goals/${goalToDelete.value.id}`,
            );
            toast.success('Goal deleted successfully');
            isDeleteModalOpen.value = false;
            fetchGoals();
        } catch {
            toast.error('Failed to delete goal');
        }
    };

    return {
        goals,
        isLoading,
        isError,
        isModalOpen,
        isDeleteModalOpen,
        editingGoal,
        goalToDelete,
        form,
        fetchGoals,
        openCreate,
        openEdit,
        confirmDelete,
        saveGoal,
        deleteGoal,
    };
}
