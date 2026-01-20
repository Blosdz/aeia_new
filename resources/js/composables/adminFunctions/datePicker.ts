import {
    DateFormatter,
    type DateValue,
    getLocalTimeZone,
} from '@internationalized/date'
import { ref } from 'vue'


const df = new DateFormatter('en-US', {
    dateStyle: 'long',
  })
  
export const value = ref<DateValue>()