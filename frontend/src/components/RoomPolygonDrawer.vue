<template>
  <div class="flex flex-col gap-2">
    <label class="text-sm font-semibold text-slate-700">Koordinat Ruangan</label>

    <!-- Canvas Container (fixed viewport) -->
    <div
      ref="containerRef"
      class="border border-slate-200 rounded-xl overflow-hidden bg-slate-50 relative select-none"
      style="height: 450px;"
    >
      <!-- Loading -->
      <div v-if="!imageLoaded" class="flex items-center justify-center h-full text-slate-400 text-sm">
        <i class="pi pi-spin pi-spinner mr-2"></i> Memuat gambar denah...
      </div>

      <!-- Konva Stage -->
      <v-stage
        v-if="imageLoaded"
        ref="stageRef"
        :config="stageConfig"
        @mousedown="handleMouseDown"
        @mouseup="handleMouseUp"
        @mousemove="handleMouseMove"
        @touchstart="handleMouseDown"
        @touchend="handleMouseUp"
        @wheel="handleWheel"
        @click="handleStageClick"
        @tap="handleStageClick"
        :style="{ cursor: isPanning ? 'grabbing' : (spacePressed ? 'grab' : 'crosshair') }"
      >
        <v-layer ref="layerRef" :config="layerConfig">
          <!-- Floor plan image -->
          <v-image :config="imageConfig" />

          <!-- Filled polygon (when >= 4 points) -->
          <v-line
            v-if="points.length >= 4"
            :config="{
              points: flatPoints,
              fill: 'rgba(16, 185, 129, 0.2)',
              stroke: 'rgba(16, 185, 129, 0.6)',
              strokeWidth: 2 / scale,
              closed: true,
              listening: false
            }"
          />

          <!-- Outline when < 4 points but > 1 -->
          <v-line
            v-if="points.length >= 2 && points.length < 4"
            :config="{
              points: flatPoints,
              stroke: 'rgba(16, 185, 129, 0.8)',
              strokeWidth: 2 / scale,
              closed: false,
              listening: false
            }"
          />

          <!-- Vertex circles -->
          <v-circle
            v-for="(point, index) in points"
            :key="'point-' + index"
            :config="{
              x: point.x,
              y: point.y,
              radius: 6 / scale,
              fill: index === 0 ? '#059669' : '#10b981',
              stroke: '#fff',
              strokeWidth: 2 / scale,
              draggable: !spacePressed && !isPanning
            }"
            @dragstart="handlePointDragStart"
            @dragmove="(e) => handlePointDrag(e, index)"
            @dragend="handlePointDragEnd"
          />
        </v-layer>
      </v-stage>

      <!-- Overlay Controls (always visible on top of canvas) -->
      <div v-if="imageLoaded" class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/50 to-transparent px-4 py-3 pointer-events-none">
        <div class="flex items-center justify-between pointer-events-auto">
          <div class="flex items-center gap-3">
            <span class="text-xs text-white/90 font-medium">
              Titik: <strong>{{ points.length }}</strong>
            </span>
            <span class="text-xs text-white/60">
              Zoom: {{ Math.round(scale * 100) }}%
            </span>
          </div>
          <div class="flex gap-2">
            <button
              type="button"
              class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-lg bg-white/90 text-slate-700 hover:bg-white transition-colors disabled:opacity-40 disabled:cursor-not-allowed shadow-sm"
              :disabled="points.length === 0"
              @click="undoLastPoint"
            >
              <i class="pi pi-undo text-xs"></i> Undo
            </button>
            <button
              type="button"
              class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-lg bg-white/90 text-red-600 hover:bg-white transition-colors disabled:opacity-40 disabled:cursor-not-allowed shadow-sm"
              :disabled="points.length === 0"
              @click="resetAllPoints"
            >
              <i class="pi pi-trash text-xs"></i> Reset
            </button>
            <button
              type="button"
              class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-lg bg-white/90 text-slate-700 hover:bg-white transition-colors shadow-sm"
              @click="resetView"
            >
              <i class="pi pi-arrows-alt text-xs"></i> Fit
            </button>
          </div>
        </div>
      </div>

      <!-- Hint overlay (top-right) -->
      <div v-if="imageLoaded" class="absolute top-2 right-2 text-[10px] text-white/70 bg-black/40 rounded-md px-2 py-1 pointer-events-none">
        Scroll = Zoom &middot; Tahan Space + Drag = Geser
      </div>
    </div>

    <!-- Validation message -->
    <small v-if="points.length > 0 && points.length < 4" class="text-amber-600 text-xs flex items-center gap-1">
      <i class="pi pi-exclamation-triangle text-xs"></i>
      Minimal 4 titik diperlukan untuk membentuk area ruangan (saat ini: {{ points.length }})
    </small>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'

const props = defineProps({
  floorImageUrl: {
    type: String,
    required: true
  },
  initialPoints: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['update:points'])

const containerRef = ref(null)
const stageRef = ref(null)
const layerRef = ref(null)
const imageLoaded = ref(false)
const floorImage = ref(null)
const points = ref([])

// Pan & zoom state
const scale = ref(1)
const offsetX = ref(0)
const offsetY = ref(0)
const isPanning = ref(false)
const spacePressed = ref(false)
const panStart = ref({ x: 0, y: 0 })
const lastOffset = ref({ x: 0, y: 0 })
const didPan = ref(false)

const SCALE_MIN = 0.2
const SCALE_MAX = 5
const SCALE_STEP = 1.1

const stageConfig = ref({
  width: 800,
  height: 450
})

const layerConfig = computed(() => ({
  scaleX: scale.value,
  scaleY: scale.value,
  x: offsetX.value,
  y: offsetY.value
}))

const imageConfig = computed(() => ({
  image: floorImage.value,
  x: 0,
  y: 0,
  width: imgDisplayWidth.value,
  height: imgDisplayHeight.value,
  listening: false
}))

const imgDisplayWidth = ref(800)
const imgDisplayHeight = ref(450)

const flatPoints = computed(() => {
  return points.value.flatMap(p => [p.x, p.y])
})

// Convert screen position to image coordinates
const screenToImage = (screenX, screenY) => {
  return {
    x: Math.round((screenX - offsetX.value) / scale.value),
    y: Math.round((screenY - offsetY.value) / scale.value)
  }
}

const loadImage = () => {
  if (!props.floorImageUrl) return

  imageLoaded.value = false
  const img = new window.Image()
  img.onload = () => {
    floorImage.value = img

    const containerWidth = containerRef.value?.clientWidth || 800
    const containerHeight = 450
    const aspectRatio = img.height / img.width

    // Fit image within container
    let w = containerWidth
    let h = w * aspectRatio
    if (h > containerHeight) {
      h = containerHeight
      w = h / aspectRatio
    }

    imgDisplayWidth.value = w
    imgDisplayHeight.value = h

    stageConfig.value = {
      width: containerWidth,
      height: containerHeight
    }

    // Center image
    scale.value = 1
    offsetX.value = (containerWidth - w) / 2
    offsetY.value = (containerHeight - h) / 2

    imageLoaded.value = true
  }
  img.onerror = () => {
    imageLoaded.value = false
  }
  img.src = props.floorImageUrl
}

const resetView = () => {
  const containerWidth = containerRef.value?.clientWidth || 800
  const containerHeight = 450
  scale.value = 1
  offsetX.value = (containerWidth - imgDisplayWidth.value) / 2
  offsetY.value = (containerHeight - imgDisplayHeight.value) / 2
}

// --- Zoom (mouse wheel) ---
const handleWheel = (e) => {
  e.evt.preventDefault()
  const stage = stageRef.value?.getStage()
  if (!stage) return

  const pointer = stage.getPointerPosition()
  if (!pointer) return

  const direction = e.evt.deltaY < 0 ? 1 : -1
  const newScale = direction > 0
    ? Math.min(scale.value * SCALE_STEP, SCALE_MAX)
    : Math.max(scale.value / SCALE_STEP, SCALE_MIN)

  // Zoom toward pointer
  const mousePointTo = {
    x: (pointer.x - offsetX.value) / scale.value,
    y: (pointer.y - offsetY.value) / scale.value
  }

  scale.value = newScale
  offsetX.value = pointer.x - mousePointTo.x * newScale
  offsetY.value = pointer.y - mousePointTo.y * newScale
}

// --- Pan (Space + drag OR middle mouse button) ---
const handleMouseDown = (e) => {
  const evt = e.evt
  const isMiddleBtn = evt && evt.button === 1
  if (!spacePressed.value && !isMiddleBtn) return
  isPanning.value = true
  didPan.value = false
  const stage = stageRef.value?.getStage()
  const pointer = stage?.getPointerPosition()
  if (pointer) {
    panStart.value = { x: pointer.x, y: pointer.y }
    lastOffset.value = { x: offsetX.value, y: offsetY.value }
  }
}

const handleMouseMove = (e) => {
  if (!isPanning.value) return
  const stage = stageRef.value?.getStage()
  const pointer = stage?.getPointerPosition()
  if (!pointer) return

  const dx = pointer.x - panStart.value.x
  const dy = pointer.y - panStart.value.y
  if (Math.abs(dx) > 2 || Math.abs(dy) > 2) didPan.value = true

  offsetX.value = lastOffset.value.x + dx
  offsetY.value = lastOffset.value.y + dy
}

const handleMouseUp = () => {
  isPanning.value = false
}

// --- Click to add point ---
const handleStageClick = (e) => {
  // Don't add point if we were panning, space is held, or dragging a point
  if (spacePressed.value || didPan.value || isDraggingPoint.value) return

  const stage = e.target.getStage()
  const pointerPos = stage.getPointerPosition()
  if (!pointerPos) return

  // Don't add point if clicking on a circle (dragging)
  if (e.target !== stage && e.target.className === 'Circle') return

  const imgPoint = screenToImage(pointerPos.x, pointerPos.y)

  points.value = [...points.value, imgPoint]
  emit('update:points', [...points.value])
}

const isDraggingPoint = ref(false)

const handlePointDragStart = (e) => {
  // Cancel drag if panning or if not left mouse button
  if (isPanning.value || spacePressed.value) {
    e.target.stopDrag()
    return
  }
  isDraggingPoint.value = true
}

const handlePointDrag = (e, index) => {
  if (!isDraggingPoint.value) return
  const newPoints = [...points.value]
  newPoints[index] = {
    x: Math.round(e.target.x()),
    y: Math.round(e.target.y())
  }
  points.value = newPoints
  emit('update:points', [...points.value])
}

const handlePointDragEnd = () => {
  isDraggingPoint.value = false
}

const undoLastPoint = () => {
  if (points.value.length > 0) {
    points.value = points.value.slice(0, -1)
    emit('update:points', [...points.value])
  }
}

const resetAllPoints = () => {
  points.value = []
  emit('update:points', [])
}

// Keyboard listeners for Space key
const onKeyDown = (e) => {
  if (e.code === 'Space' && !e.repeat) {
    e.preventDefault()
    spacePressed.value = true
  }
}
const onKeyUp = (e) => {
  if (e.code === 'Space') {
    e.preventDefault()
    spacePressed.value = false
    isPanning.value = false
  }
}

watch(() => props.floorImageUrl, () => {
  loadImage()
})

watch(() => props.initialPoints, (newVal) => {
  if (newVal && newVal.length > 0) {
    points.value = newVal.map(p => ({ x: p.x, y: p.y }))
  }
}, { immediate: true })

onMounted(() => {
  window.addEventListener('keydown', onKeyDown)
  window.addEventListener('keyup', onKeyUp)
  nextTick(() => {
    loadImage()
  })
})

onBeforeUnmount(() => {
  window.removeEventListener('keydown', onKeyDown)
  window.removeEventListener('keyup', onKeyUp)
})
</script>
