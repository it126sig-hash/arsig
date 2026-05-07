<template>
  <div class="flex flex-col gap-2 h-full">
    <!-- Canvas Container (fixed viewport) -->
    <div
      ref="containerRef"
      class="border border-slate-200 rounded-xl overflow-hidden bg-slate-50 relative select-none h-full"
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
        :style="{ cursor: isPanning ? 'grabbing' : 'default' }"
      >
        <v-layer ref="layerRef" :config="layerConfig">
          <!-- Floor plan image -->
          <v-image :config="imageConfig" />


          <!-- Room Polygon (Blue) -->
          <v-line
            v-if="highlightedRoomCoords && highlightedRoomCoords.length >= 4"
            :config="{
              points: flatRoomPoints,
              fill: 'rgba(59, 130, 246, 0.25)',
              stroke: 'rgba(59, 130, 246, 0.8)',
              strokeWidth: 2 / scale,
              closed: true,
              listening: false
            }"
          />

          <!-- Cabinet Polygon (Orange) -->
          <v-line
            v-if="highlightedCabinetCoords && highlightedCabinetCoords.length >= 4"
            :config="{
              points: flatCabinetPoints,
              fill: 'rgba(245, 158, 11, 0.3)',
              stroke: 'rgba(245, 158, 11, 0.9)',
              strokeWidth: 2 / scale,
              closed: true,
              listening: false
            }"
          />
        </v-layer>


      </v-stage>

      <!-- Overlay Controls -->
      <div v-if="imageLoaded" class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/50 to-transparent px-4 py-3 pointer-events-none">
        <div class="flex items-center justify-between pointer-events-auto">
          <div class="flex items-center gap-3">
            <span class="text-xs text-white/60">
              Zoom: {{ Math.round(scale * 100) }}%
            </span>
          </div>
          <div class="flex gap-2">
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

      <!-- Hint overlay -->
      <div v-if="imageLoaded" class="absolute top-2 right-2 text-[10px] text-white/70 bg-black/40 rounded-md px-2 py-1 pointer-events-none">
        Scroll = Zoom &middot; Drag = Geser peta
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue'

const props = defineProps({
  imageUrl: {
    type: String,
    required: true
  },
  highlightedRoomCoords: {
    type: Array,
    default: () => []
  },
  highlightedCabinetCoords: {
    type: Array,
    default: () => []
  }
})



const containerRef = ref(null)
const stageRef = ref(null)
const layerRef = ref(null)
const imageLoaded = ref(false)
const floorImage = ref(null)

// Pan & zoom state
const scale = ref(1)
const offsetX = ref(0)
const offsetY = ref(0)
const isPanning = ref(false)
const potentialPan = ref(false)
const panStart = ref({ x: 0, y: 0 })
const lastOffset = ref({ x: 0, y: 0 })

const SCALE_MIN = 0.2
const SCALE_MAX = 5
const SCALE_STEP = 1.1
const PAN_THRESHOLD = 5

const stageConfig = ref({
  width: 400,
  height: 300
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

const imgDisplayWidth = ref(400)
const imgDisplayHeight = ref(300)
const naturalWidth = ref(1)
const naturalHeight = ref(1)

const scalePoints = (polygon) => {
  if (!polygon || !imageLoaded.value) return []
  
  // Check first point to guess format
  const firstPoint = polygon[0]
  let isNormalized = false
  if (firstPoint && Math.abs(firstPoint.x) <= 1.2 && Math.abs(firstPoint.y) <= 1.2) {
    isNormalized = true
  }

  return polygon.flatMap(p => {
    let x = p.x
    let y = p.y

    if (isNormalized) {
      x = x * imgDisplayWidth.value
      y = y * imgDisplayHeight.value
    } else {
      const scaleFactor = imgDisplayHeight.value / 450
      x = x * scaleFactor
      y = y * scaleFactor
    }

    return [x, y]
  })
}

const flatRoomPoints = computed(() => scalePoints(props.highlightedRoomCoords))
const flatCabinetPoints = computed(() => scalePoints(props.highlightedCabinetCoords))

const loadImage = () => {


  if (!props.imageUrl) return

  imageLoaded.value = false
  const img = new window.Image()
  img.onload = () => {
    floorImage.value = img
    naturalWidth.value = img.naturalWidth
    naturalHeight.value = img.naturalHeight

    const containerWidth = containerRef.value?.clientWidth || 400

    const containerHeight = containerRef.value?.clientHeight || 300
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
  img.src = props.imageUrl
}

const resetView = () => {
  const containerWidth = containerRef.value?.clientWidth || 400
  const containerHeight = containerRef.value?.clientHeight || 300
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

// --- Pan (left-click-drag) ---
const handleMouseDown = (e) => {
  const evt = e.evt
  const isLeftBtn = evt && evt.button === 0
  const isMiddleBtn = evt && evt.button === 1
  if (!isLeftBtn && !isMiddleBtn) return

  potentialPan.value = true
  isPanning.value = false
  const stage = stageRef.value?.getStage()
  const pointer = stage?.getPointerPosition()
  if (pointer) {
    panStart.value = { x: pointer.x, y: pointer.y }
    lastOffset.value = { x: offsetX.value, y: offsetY.value }
  }
}

const handleMouseMove = () => {
  if (!potentialPan.value) return
  const stage = stageRef.value?.getStage()
  const pointer = stage?.getPointerPosition()
  if (!pointer) return

  const dx = pointer.x - panStart.value.x
  const dy = pointer.y - panStart.value.y

  if (!isPanning.value && (Math.abs(dx) > PAN_THRESHOLD || Math.abs(dy) > PAN_THRESHOLD)) {
    isPanning.value = true
  }

  if (isPanning.value) {
    offsetX.value = lastOffset.value.x + dx
    offsetY.value = lastOffset.value.y + dy
  }
}

const handleMouseUp = () => {
  isPanning.value = false
  potentialPan.value = false
}

watch(() => props.imageUrl, () => {
  loadImage()
})

onMounted(() => {
  nextTick(() => {
    loadImage()
  })
})
</script>
