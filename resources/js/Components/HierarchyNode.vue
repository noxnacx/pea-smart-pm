<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
  node: Object,     // ข้อมูล (Program หรือ Project)
  type: String,     // 'program' หรือ 'project'
  level: { type: Number, default: 0 } // ระดับความลึก
});

const emit = defineEmits(['add-program', 'add-project', 'edit', 'delete', 'click-node']);

const isOpen = ref(true); // เปิด/ปิด folder

const hasChildren = computed(() => {
    // เช็คว่ามีลูกไหม (Program มี children+projects, Project มี children)
    if (props.type === 'program') {
        return (props.node.children && props.node.children.length > 0) ||
               (props.node.projects && props.node.projects.length > 0);
    }
    return props.node.children && props.node.children.length > 0;
});

const toggle = () => {
    if (hasChildren.value) isOpen.value = !isOpen.value;
};

// สีตามระดับความลึก
const getBorderColor = () => {
    const colors = ['border-l-purple-500', 'border-l-blue-400', 'border-l-green-400', 'border-l-yellow-400'];
    return colors[props.level % colors.length];
};
</script>

<template>
  <div class="select-none">
    <div
        class="flex items-center gap-2 py-2 px-3 hover:bg-gray-50 rounded-lg transition-colors group border-l-4"
        :class="[getBorderColor(), level > 0 ? 'ml-6' : '']"
    >
      <button
        v-if="hasChildren"
        @click.stop="toggle"
        class="p-0.5 rounded hover:bg-gray-200 text-gray-500 transition-transform duration-200"
        :class="{'rotate-90': isOpen}"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
      </button>
      <div v-else class="w-5"></div> <span v-if="type === 'program'" class="text-purple-600" title="แผนงาน">📂</span>
      <span v-else class="text-blue-600" title="โครงการ">🏗️</span>

      <div class="flex-1 cursor-pointer" @click="$emit('click-node', { node, type })">
         <div class="flex items-center gap-2">
             <span class="font-medium text-gray-800 text-sm">{{ node.name }}</span>
             <span v-if="node.code" class="text-xs bg-gray-100 text-gray-500 px-1.5 rounded">{{ node.code }}</span>
         </div>
         <div class="text-[10px] text-gray-400 hidden group-hover:block animate-fade-in">
             คลิกเพื่อดูรายละเอียด
         </div>
      </div>

      <div class="hidden group-hover:flex items-center gap-1 opacity-60 hover:opacity-100">
          <template v-if="type === 'program'">
              <button @click="$emit('add-program', node)" class="p-1 hover:bg-purple-100 text-purple-600 rounded" title="เพิ่มแผนงานย่อย">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" /></svg>
              </button>
              <button @click="$emit('add-project', node)" class="p-1 hover:bg-blue-100 text-blue-600 rounded" title="เพิ่มโครงการ">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
              </button>
          </template>

          <template v-if="type === 'project'">
             <button @click="$emit('add-project', node)" class="p-1 hover:bg-blue-100 text-blue-600 rounded" title="เพิ่มโครงการย่อย">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
              </button>
          </template>

          <button @click="$emit('edit', { node, type })" class="p-1 hover:bg-yellow-100 text-yellow-600 rounded" title="แก้ไข"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" /></svg></button>
          <button @click="$emit('delete', { node, type })" class="p-1 hover:bg-red-100 text-red-600 rounded" title="ลบ"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></svg></button>
      </div>
    </div>

    <div v-if="isOpen && hasChildren" class="transition-all duration-300">
        <HierarchyNode
            v-for="child in node.children"
            :key="'prog-'+child.id"
            :node="child"
            type="program"
            :level="level + 1"
            @add-program="$emit('add-program', $event)"
            @add-project="$emit('add-project', $event)"
            @edit="$emit('edit', $event)"
            @delete="$emit('delete', $event)"
            @click-node="$emit('click-node', $event)"
        />

        <HierarchyNode
            v-if="type === 'program' && node.projects"
            v-for="proj in node.projects"
            :key="'proj-'+proj.id"
            :node="proj"
            type="project"
            :level="level + 1"
            @add-project="$emit('add-project', $event)"
            @edit="$emit('edit', $event)"
            @delete="$emit('delete', $event)"
            @click-node="$emit('click-node', $event)"
        />

        <HierarchyNode
            v-if="type === 'project' && node.children"
            v-for="subProj in node.children"
            :key="'sub-proj-'+subProj.id"
            :node="subProj"
            type="project"
            :level="level + 1"
            @add-project="$emit('add-project', $event)"
            @edit="$emit('edit', $event)"
            @delete="$emit('delete', $event)"
            @click-node="$emit('click-node', $event)"
        />
    </div>
  </div>
</template>
