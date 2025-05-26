<template>
    <div class="mb-6 grid gap-4 md:grid-cols-4">
        <div class="space-y-2">
            <div>
                <Label for="status">Publish Status</Label>
            </div>
            <Select v-model="filters.status">
                <SelectTrigger id="post-status">
                    <SelectValue placeholder="Select Status" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem @select="filters.status = null">
                        All Status
                    </SelectItem>
                    <SelectItem v-for="(status, index) in publishStatus" :key="index" :value="status.value">
                        {{ status.label }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>
        <div class="space-y-2 flex flex-col">
            <div>
                <Label for="sort-by">Date</Label>
            </div>
            <Calendar @bindCalendarDate="bindCalendarDate" />
        </div>
        <div class="mb-2 flex items-end">
            <div>
                <Button variant="outline" @click.prevent="resetFields">
                    <X />
                    Reset
                </Button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import Select from '../ui/select/Select.vue';
import SelectContent from '../ui/select/SelectContent.vue';
import SelectItem from '../ui/select/SelectItem.vue';
import SelectTrigger from '../ui/select/SelectTrigger.vue';
import Calendar from '../Calendar.vue';
import SelectValue from '../ui/select/SelectValue.vue';
import { onMounted, ref, watch } from 'vue';
import { debounce } from 'lodash'
import Button from '../ui/button/Button.vue';
import { X } from 'lucide-vue-next';
import eventBus from '@/composables/eventBus';
const propsData = defineProps({
    publishStatus: Object,
    filters: Array,
});
const filters = ref({
    status: '',
    date: '',
});
const bindCalendarDate = (date) => {
    const formatedDate = new Date(date);
    filters.value.date = formatedDate.toLocaleDateString('sv-SE');
}

const submitFilter = debounce(() => {
    router.get(route('posts.index', filters.value), {}, {
        preserveScroll: true,
        preserveState: true
    });
}, 500)

const resetFields = () => {
    filters.value = {
        status: null,
        date: null,
    }
    eventBus.emit('resetDateField')
}

watch(filters, () => {
    submitFilter();
}, { deep: true })


onMounted(() => {
    filters.value = { ...propsData.filters };
    console.log(filters.value);
})

</script>