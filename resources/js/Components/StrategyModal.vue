<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';

const props = defineProps(['isOpen', 'editData']);
const emit = defineEmits(['close', 'saved']);

const form = ref({ name: '', description: '' });
const loading = ref(false);

watch(() => props.isOpen, (val) => {
    if(val) {
        if(props.editData) {
            form.value = { ...props.editData };
        } else {
            form.value = { name: '', description: '' };
        }
    }
});

const save = async () => {
    if(!form.value.name) return alert('กรุณาระบุชื่อยุทธศาสตร์');

    loading.value = true;
    try {
        if(form.value.id) {
            await axios.put(`/api/strategies/${form.value.id}`, form.value);
        } else {
            await axios.post('/api/strategies', form.value);
        }
        emit('saved');
    } catch (e) {
        alert('บันทึกไม่สำเร็จ');
        console.error(e);
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="$emit('close')"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">
                        {{ form.id ? 'แก้ไขยุทธศาสตร์' : 'เพิ่มยุทธศาสตร์ใหม่' }}
                    </h3>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">ชื่อยุทธศาสตร์ <span class="text-red-500">*</span></label>
                            <input v-model="form.name" type="text" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-purple-500 focus:border-purple-500 sm:text-sm" placeholder="เช่น พัฒนาความมั่นคงทางพลังงาน">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">รายละเอียด (Optional)</label>
                            <textarea v-model="form.description" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-purple-500 focus:border-purple-500 sm:text-sm"></textarea>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                    <button @click="save" :disabled="loading" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-purple-600 text-base font-medium text-white hover:bg-purple-700 focus:outline-none disabled:opacity-50 sm:ml-3 sm:w-auto sm:text-sm">
                        {{ loading ? 'กำลังบันทึก...' : 'บันทึก' }}
                    </button>
                    <button @click="$emit('close')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        ยกเลิก
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
