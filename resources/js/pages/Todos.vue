<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

const breadcrumbs = [
    { title: 'Todos', href: '/todos' },
];

const page = usePage();

const todos = ref(page.props.todos?.data || []);
const pagination = ref({
    current_page: page.props.todos?.current_page || 1,
    last_page: page.props.todos?.last_page || 1,
    per_page: page.props.todos?.per_page || 10,
    total: page.props.todos?.total || 0,
});

watch(() => page.props.todos, (newTodos) => {
    todos.value = newTodos?.data || [];
    pagination.value = {
        current_page: newTodos?.current_page || 1,
        last_page: newTodos?.last_page || 1,
        per_page: newTodos?.per_page || 10,
        total: newTodos?.total || 0,
    };
}, { immediate: true });

const newTodo = ref('');
const loading = ref(false);
const error = ref('');

const editingId = ref<number | null>(null);
const editedTitle = ref('');

const addTodo = () => {
    if (!newTodo.value.trim()) return;
    loading.value = true;
    error.value = '';
    router.post('/todos', { title: newTodo.value }, {
        onSuccess: () => {
            newTodo.value = '';
            router.reload({ only: ['todos'] });
        },
        onError: (errors) => {
            error.value = errors.title || 'Failed to add todo.';
        },
        onFinish: () => {
            loading.value = false;
        },
    });
};

const toggleComplete = (todo) => {
    loading.value = true;
    error.value = '';
    router.put(`/todos/${todo.id}`, { completed: !todo.completed, title: todo.title }, {
        onSuccess: () => router.reload({ only: ['todos'] }),
        onError: () => {
            error.value = 'Failed to update todo.';
        },
        onFinish: () => {
            loading.value = false;
        },
    });
};

const deleteTodo = (todo) => {
    if (!confirm('Are you sure you want to delete this todo?')) return;
    loading.value = true;
    error.value = '';
    router.delete(`/todos/${todo.id}`, {
        onSuccess: () => router.reload({ only: ['todos'] }),
        onError: () => {
            error.value = 'Failed to delete todo.';
        },
        onFinish: () => {
            loading.value = false;
        },
    });
};

const startEditing = (todo) => {
    editingId.value = todo.id;
    editedTitle.value = todo.title;
};

const cancelEditing = () => {
    editingId.value = null;
    editedTitle.value = '';
};

const submitEdit = (todo) => {
    if (!editedTitle.value.trim()) {
        cancelEditing();
        return;
    }
    loading.value = true;
    error.value = '';
    router.put(`/todos/${todo.id}`, { title: editedTitle.value, completed: todo.completed }, {
        onSuccess: () => {
            router.reload({ only: ['todos'] });
            cancelEditing();
        },
        onError: () => {
            error.value = 'Failed to update todo.';
        },
        onFinish: () => {
            loading.value = false;
        },
    });
};

const goToPage = (page) => {
    if (page >= 1 && page <= pagination.value.last_page) {
        router.visit('/todos', {
            method: 'get',
            data: { page },
            preserveState: true,
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Todos" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
            <div class="max-w-7xl mx-auto w-full">
                <form @submit.prevent="addTodo" class="mb-6 flex gap-4">
                    <input
                        v-model="newTodo"
                        type="text"
                        placeholder="Add a new todo"
                        required
                        :disabled="loading"
                        class="flex-grow rounded-md border border-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-red-500 dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                    />
                    <button
                        type="submit"
                        :disabled="loading"
                        class="rounded-md bg-red-600 px-6 py-2 text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 disabled:opacity-50"
                    >
                        <span v-if="loading">Adding...</span>
                        <span v-else>Add</span>
                    </button>
                </form>

                <p v-if="error" class="text-red-600 mb-4">{{ error }}</p>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div
                        v-for="todo in todos"
                        :key="todo.id"
                        class="flex flex-col justify-between rounded-xl border border-sidebar-border/70 bg-white p-6 shadow-sm dark:border-sidebar-border dark:bg-gray-900"
                    >
                        <div class="flex items-center gap-3">
                            <input
                                type="checkbox"
                                :checked="todo.completed"
                                @change="toggleComplete(todo)"
                                :disabled="loading"
                                class="h-5 w-5 rounded border-gray-300 text-red-600 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-800"
                            />
                            <template v-if="editingId === todo.id">
                                <input
                                    v-model="editedTitle"
                                    @blur="submitEdit(todo)"
                                    @keydown.enter.prevent="submitEdit(todo)"
                                    @keydown.esc.prevent="cancelEditing"
                                    :disabled="loading"
                                    class="flex-grow rounded border border-gray-300 px-2 py-1 focus:outline-none focus:ring-2 focus:ring-red-500 dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                                    autofocus
                                />
                            </template>
                            <template v-else>
                                <span
                                    @click="startEditing(todo)"
                                    :class="{
                                        'line-through text-gray-400 dark:text-gray-500': todo.completed,
                                        'text-gray-900 dark:text-gray-100 cursor-pointer': !todo.completed,
                                    }"
                                    class="select-none text-lg font-medium"
                                    tabindex="0"
                                    @keydown.enter.prevent="startEditing(todo)"
                                >
                                    {{ todo.title }}
                                </span>
                            </template>
                        </div>
                        <button
                            @click="deleteTodo(todo)"
                            :disabled="loading"
                            class="mt-4 self-end rounded bg-red-600 px-4 py-1 text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 disabled:opacity-50"
                            aria-label="Delete todo"
                        >
                            Delete
                        </button>
                    </div>
                </div>

                <!-- Pagination -->
                <nav class="mt-8 flex justify-center space-x-2">
                    <button
                        @click="goToPage(pagination.current_page - 1)"
                        :disabled="pagination.current_page === 1 || loading"
                        class="rounded px-4 py-2 border border-gray-300 dark:border-sidebar-border disabled:opacity-50"
                    >
                        Previous
                    </button>

                    <button
                        v-for="page in pagination.last_page"
                        :key="page"
                        @click="goToPage(page)"
                        :class="[
                            'rounded px-4 py-2 border cursor-pointer',
                            page === pagination.current_page
                                ? 'bg-red-600 text-white border-red-600'
                                : 'border-gray-300 dark:border-sidebar-border',
                        ]"
                        :disabled="loading"
                    >
                        {{ page }}
                    </button>

                    <button
                        @click="goToPage(pagination.current_page + 1)"
                        :disabled="pagination.current_page === pagination.last_page || loading"
                        class="rounded px-4 py-2 border border-gray-300 dark:border-sidebar-border disabled:opacity-50"
                    >
                        Next
                    </button>
                </nav>
            </div>
        </div>
    </AppLayout>
</template>

