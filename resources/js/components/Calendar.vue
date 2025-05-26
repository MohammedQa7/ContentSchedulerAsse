<template>
    <Popover>
        <PopoverTrigger as-child>
            <Button variant="outline" :class="cn(
                ' ps-3 text-start font-normal',
                !date && 'text-muted-foreground',
            )">
                <span>{{ date
                    ? df.format(toDate(date))
                    : "Pick a date" }}</span>
                <CalendarIcon class="ms-auto h-4 w-4 opacity-50" />
            </Button>
            <input hidden>
        </PopoverTrigger>
        <PopoverContent class="w-auto p-0 ">
            <Calendar v-model="date" calendar-label="Date of birth" />
        </PopoverContent>
    </Popover>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import {
    CalendarIcon
} from 'lucide-vue-next'

import Button from '@/components/ui/button/Button.vue'
import Calendar from '@/components/ui/calendar/Calendar.vue'
import Popover from '@/components/ui/popover/Popover.vue'
import PopoverTrigger from '@/components/ui/popover/PopoverTrigger.vue'
import PopoverContent from '@/components/ui/popover/PopoverContent.vue'
import { cn } from '@/lib/utils'
import { DateFormatter, parseDate, CalendarDate } from '@internationalized/date'

import { watch } from 'vue'
import { toDate } from 'radix-vue/date'
import eventBus from '@/composables/eventBus'
const df = new DateFormatter('en-US', { dateStyle: 'long' })
const date = ref();
const propsData = defineProps({
    selectedDate: String,
});
const emit = defineEmits();

eventBus.on('resetDateField', () => {
    date.value = null;
})
watch(date, () => {
    if (date.value) {
        const formatedDate = date.value.day + '/' + date.value.month + '/' + date.value.year;
        emit('bindCalendarDate', date.value);
    }
})
onMounted(() => {

    if (propsData.selectedDate) {
        const isoDateString = propsData.selectedDate.replace(' ', 'T');
        const dateObj = new Date(isoDateString);
        const calendarDate = {
            calendar: {
                identifier: 'gregory'
            },
            era: dateObj.getFullYear() >= 1 ? "AD" : "BC",
            year: dateObj.getFullYear(),
            month: dateObj.getMonth() + 1,
            day: dateObj.getDate()

        };
        // Create a CalendarDate object
        const calendar = new CalendarDate(calendarDate.year,
            calendarDate.month,
            calendarDate.day); // year, month, day

        date.value = calendar
    }


    // date.value = propsData.selectedDate ? new Date(propsData.selectedDate) : '';
})

</script>
