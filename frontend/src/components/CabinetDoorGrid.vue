<template>
  <div v-if="cols > 0 && rows > 0" class="flex flex-col gap-3">
    <div class="flex items-center justify-between">
      <h3 class="text-sm font-semibold text-slate-700">
        <i class="pi pi-th-large mr-1.5 text-orange-500"></i>
        Visualisasi Pintu — {{ cabinetName }} ({{ cols }} × {{ rows }} = {{ totalDoors }} pintu)
      </h3>
    </div>

    <div
      class="grid gap-2"
      :style="{ gridTemplateColumns: `repeat(${cols}, minmax(0, 1fr))` }"
    >
      <div
        v-for="(slot, idx) in normalizedSlots"
        :key="slot.id || idx"
        class="border-2 rounded-lg p-2.5 cursor-pointer transition-all hover:shadow-md hover:scale-[1.02] flex flex-col items-center justify-center min-h-[100px] text-center"
        :class="[doorColorClass(slot.status), { 'ring-4 ring-orange-400 border-orange-500 shadow-lg scale-105 z-10': slot.id && slot.id === highlightedSlotId }]"
        @click="$emit('slot-click', slot, idx)"
      >
        <!-- Door number -->
        <span class="text-sm font-bold text-slate-700" :class="{ 'text-orange-700': slot.id && slot.id === highlightedSlotId }">{{ slot.name || String(idx + 1).padStart(2, '0') }}</span>

        <!-- PIC list -->
        <div v-if="slot.pic_users && slot.pic_users.length > 0" class="mt-1.5 flex flex-wrap gap-1 justify-center">
          <span
            v-for="pic in slot.pic_users"
            :key="pic.id"
            class="inline-flex items-center gap-1 px-1.5 py-0.5 bg-white/70 rounded text-[10px] text-slate-600 font-medium"
          >
            <span class="w-3.5 h-3.5 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-[8px] font-bold flex-shrink-0">
              {{ pic.name[0].toUpperCase() }}
            </span>
            {{ pic.name }}
          </span>
        </div>
        <span v-else class="mt-1 text-[10px] text-slate-400 italic">Belum ada PIC</span>

        <!-- Status badge -->
        <span
          class="mt-1.5 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium"
          :class="statusBadgeClass(slot.status)"
        >
          {{ statusLabel(slot.status) }}
        </span>

        <!-- Tags -->
        <div v-if="slot.tags && slot.tags.length > 0" class="mt-1 flex flex-wrap gap-0.5 justify-center">
          <span
            v-for="tag in slot.tags"
            :key="tag.id"
            class="inline-flex items-center px-1 py-0 rounded bg-violet-50 text-violet-600 text-[9px] font-medium"
          >
            #{{ tag.nama }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  doorCount: {
    type: String,
    default: ''
  },
  slots: {
    type: Array,
    default: () => []
  },
  cabinetName: {
    type: String,
    default: ''
  },
  highlightedSlotId: {
    type: [Number, String, null],
    default: null
  }
})

defineEmits(['slot-click'])

const cols = computed(() => {
  if (!props.doorCount) return 0
  const parts = props.doorCount.split('*').map(s => parseInt(s.trim()))
  return parts.length === 2 ? parts[0] : 0
})

const rows = computed(() => {
  if (!props.doorCount) return 0
  const parts = props.doorCount.split('*').map(s => parseInt(s.trim()))
  return parts.length === 2 ? parts[1] : 0
})

const totalDoors = computed(() => cols.value * rows.value)

const normalizedSlots = computed(() => {
  const total = totalDoors.value
  if (total <= 0) return []
  const result = []
  for (let i = 0; i < total; i++) {
    if (props.slots[i]) {
      result.push(props.slots[i])
    } else {
      result.push({
        id: null,
        name: String(i + 1).padStart(2, '0'),
        status: 'aktif',
        pic_users: [],
        tags: [],
        keterangan: ''
      })
    }
  }
  return result
})

const doorColorClass = (status) => {
  switch (status) {
    case 'nonaktif': return 'bg-slate-100 border-slate-300'
    case 'rusak': return 'bg-red-50 border-red-300'
    default: return 'bg-emerald-50 border-emerald-300'
  }
}

const statusBadgeClass = (status) => {
  switch (status) {
    case 'nonaktif': return 'bg-slate-200 text-slate-600'
    case 'rusak': return 'bg-red-100 text-red-600'
    default: return 'bg-emerald-100 text-emerald-700'
  }
}

const statusLabel = (status) => {
  switch (status) {
    case 'nonaktif': return 'Nonaktif'
    case 'rusak': return 'Rusak'
    default: return 'Aktif'
  }
}
</script>
