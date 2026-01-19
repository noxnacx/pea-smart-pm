<script setup>
import AppLayout from '@/Components/AppLayout.vue';
import HierarchyNode from '@/Components/HierarchyNode.vue';
import HierarchyProgramModal from '@/Components/HierarchyProgramModal.vue';
import HierarchyProjectModal from '@/Components/HierarchyProjectModal.vue'; // ✅ 1. Import Modal โปรเจกต์
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const router = useRouter();
const strategy = ref(null);
const loading = ref(true);
const programs = ref([]);

// --- State สำหรับ Program Modal ---
const showProgramModal = ref(false);
const programEditData = ref(null);
const parentIdForNewProgram = ref(null);
const parentNameForNewProgram = ref('');

// --- State สำหรับ Project Modal (✅ 2. เพิ่มส่วนนี้) ---
const showProjectModal = ref(false);
const projectEditData = ref(null);
const parentIdForNewProject = ref(null); // สำหรับ Sub-Project
const programIdForNewProject = ref(null); // สำหรับ Main Project (under Program)
const parentNameForNewProject = ref('');

// โหลดข้อมูล
const fetchStrategy = async () => {
    loading.value = true;
    try {
        const id = route.params.id;
        // 1. ดึงข้อมูลยุทธศาสตร์แม่
        const resStrat = await axios.get(`/api/strategies/${id}`);
        strategy.value = resStrat.data;

        // 2. ดึงแผนงานทั้งหมด (Root Only)
        const resProg = await axios.get(`/api/programs?strategy_id=${id}&root_only=1`);
        programs.value = resProg.data;

    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
};

// --- Actions Program ---

const openCreateRootProgram = () => {
    programEditData.value = null;
    parentIdForNewProgram.value = null;
    parentNameForNewProgram.value = '';
    showProgramModal.value = true;
};

const handleAddProgram = (parent) => {
    programEditData.value = null;
    parentIdForNewProgram.value = parent.id;
    parentNameForNewProgram.value = parent.name;
    showProgramModal.value = true;
};

// --- Actions Project (✅ 3. แก้ไขฟังก์ชันนี้) ---

const handleAddProject = (parent) => {
    projectEditData.value = null;
    parentNameForNewProject.value = parent.name;

    // Logic แยกแยะ: Parent เป็น Program หรือ Project?
    // ดูจากว่ามี field 'fiscal_year' ไหม (เพราะ Program มี, Project ไม่มี)
    if (parent.fiscal_year !== undefined) {
        // กรณีแม่เป็น Program -> สร้าง Main Project
        programIdForNewProject.value = parent.id;
        parentIdForNewProject.value = null;
    } else {
        // กรณีแม่เป็น Project -> สร้าง Sub-Project
        programIdForNewProject.value = null;
        parentIdForNewProject.value = parent.id;
    }

    showProjectModal.value = true;
};

// Common Actions
const handleEdit = ({ node, type }) => {
    if (type === 'program') {
        programEditData.value = node;
        showProgramModal.value = true;
    } else {
        // ถ้าเป็น Project แก้ไขผ่าน Modal เดียวกันได้เลย
        projectEditData.value = node;
        // Reset ค่าพ่อ (เพราะแก้ไขตัวเดิม ไม่ได้สร้างใหม่)
        programIdForNewProject.value = null;
        parentIdForNewProject.value = null;
        parentNameForNewProject.value = '';
        showProjectModal.value = true;
    }
};

const handleClickNode = ({ node, type }) => {
    if(type === 'project') {
        router.push(`/project/${node.id}`);
    }
};

const handleDelete = async ({ node, type }) => {
    if(!confirm(`ต้องการลบ ${type} "${node.name}" ใช่หรือไม่?`)) return;
    try {
        const endpoint = type === 'program' ? `/api/programs/${node.id}` : `/api/projects/${node.id}`;
        await axios.delete(endpoint);
        fetchStrategy();
    } catch (e) {
        alert('ลบไม่สำเร็จ (อาจมีข้อมูลย่อยผูกอยู่)');
    }
};

const onProgramSaved = () => {
    showProgramModal.value = false;
    fetchStrategy();
};

// ✅ เพิ่มฟังก์ชันเมื่อบันทึกโปรเจกต์เสร็จ
const onProjectSaved = () => {
    showProjectModal.value = false;
    fetchStrategy();
};

onMounted(() => {
    fetchStrategy();
});
</script>

<template>
    <AppLayout>
        <div class="h-full flex flex-col">
            <div class="mb-6">
                <button @click="$router.push('/strategies')" class="text-sm text-gray-500 hover:text-purple-600 mb-2 flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" /></svg>
                    กลับไปหน้ารวม
                </button>
                <div v-if="strategy" class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">{{ strategy.name }}</h1>
                        <p class="text-gray-500">{{ strategy.description }}</p>
                    </div>
                    <button @click="openCreateRootProgram" class="bg-purple-600 text-white px-4 py-2 rounded-lg shadow hover:bg-purple-700 flex items-center gap-2 transition-all active:scale-95">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" /></svg>
                        สร้างแผนงานหลัก
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 flex-1 overflow-hidden flex flex-col">
                <div class="p-4 border-b bg-gray-50 flex items-center justify-between">
                    <h2 class="font-semibold text-gray-700">โครงสร้างแผนงาน (Hierarchy View)</h2>
                    <div class="flex gap-4 text-xs">
                        <div class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-purple-500"></span> แผนงาน</div>
                        <div class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-blue-500"></span> โครงการ</div>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto p-4 custom-scrollbar">
                    <div v-if="loading" class="text-center py-10 text-gray-400">โหลดข้อมูล...</div>

                    <div v-else-if="programs.length === 0" class="text-center py-16">
                        <div class="text-gray-400 mb-2 text-4xl">📂</div>
                        <p class="text-gray-500">ยังไม่มีแผนงานในยุทธศาสตร์นี้</p>
                        <p class="text-xs text-gray-400 mt-1">กดปุ่ม "สร้างแผนงานหลัก" ด้านบนเพื่อเริ่มต้น</p>
                    </div>

                    <div v-else class="space-y-2">
                        <HierarchyNode
                            v-for="prog in programs"
                            :key="prog.id"
                            :node="prog"
                            type="program"
                            :level="0"
                            @add-program="handleAddProgram"
                            @add-project="handleAddProject"
                            @edit="handleEdit"
                            @delete="handleDelete"
                            @click-node="handleClickNode"
                        />
                    </div>
                </div>
            </div>

            <HierarchyProgramModal
                :isOpen="showProgramModal"
                :editData="programEditData"
                :strategyId="strategy?.id"
                :parentId="parentIdForNewProgram"
                :parentName="parentNameForNewProgram"
                @close="showProgramModal = false"
                @saved="onProgramSaved"
            />

            <HierarchyProjectModal
                :isOpen="showProjectModal"
                :editData="projectEditData"
                :programId="programIdForNewProject"
                :parentId="parentIdForNewProject"
                :parentName="parentNameForNewProject"
                @close="showProjectModal = false"
                @saved="onProjectSaved"
            />

        </div>
    </AppLayout>
</template>
