<template>
  <div ref="containerRef" class="w-full h-full min-h-[300px] relative bg-slate-50 overflow-hidden">
    <!-- Loading overlay -->
    <div v-if="!imageLoaded && !loadError" class="absolute inset-0 flex flex-col items-center justify-center bg-slate-50/80 z-10">
      <ProgressSpinner style="width: 40px; height: 40px" strokeWidth="4" />
      <p class="mt-2 text-xs text-slate-400 font-medium">Memuat denah lokasi...</p>
    </div>

    <!-- Error State -->
    <div v-if="loadError" class="absolute inset-0 flex flex-col items-center justify-center bg-slate-50 p-6 text-center z-10">
      <div class="w-12 h-12 bg-amber-50 text-amber-500 rounded-full flex items-center justify-center mb-4">
        <i class="pi pi-exclamation-triangle text-xl"></i>
      </div>
      <h3 class="text-sm font-bold text-slate-700 mb-1">Denah Tidak Tersedia</h3>
      <p class="text-xs text-slate-500 max-w-[250px]">
        {{ errorMessage }}
      </p>
    </div>

    <!-- Konva Stage -->
    <v-stage
      v-if="imageLoaded"
      ref="stageRef"
      :config="stageConfig"
      @mousedown="handleMouseDown"
      @mousemove="handleMouseMove"
      @mouseup="handleMouseUp"
      @wheel="handleWheel"
      class="cursor-grab active:cursor-grabbing"
    >
      <v-layer :config="layerConfig">
        <!-- Floor Plan Image -->
        <v-image :config="imageConfig" />

        <!-- Room Polygon -->
        <v-line
          v-if="roomPoints.length >= 4"
          :config="{
            points: roomPoints,
            fill: 'rgba(59, 130, 246, 0.1)',
            stroke: 'rgba(59, 130, 246, 0.3)',
            strokeWidth: 2 / scale,
            closed: true,
            listening: false
          }"
        />

        <!-- Cabinet Polygon (Highlighted) -->
        <v-line
          v-if="cabinetPoints.length >= 4"
          :config="{
            points: cabinetPoints,
            fill: 'rgba(234, 88, 12, 0.3)',
            stroke: 'rgba(234, 88, 12, 0.8)',
            strokeWidth: 3 / scale,
            closed: true,
            listening: false,
            shadowBlur: 10 / scale,
            shadowColor: 'rgba(234, 88, 12, 0.5)'
          }"
        />

        <!-- Cabinet Door Indicator -->
        <!-- We assume the first edge (p1 to p2) is the front/door side -->
        <v-line
          v-if="cabinetPoints.length >= 4"
          :config="{
            points: [cabinetPoints[0], cabinetPoints[1], cabinetPoints[2], cabinetPoints[3]],
            stroke: '#ef4444',
            strokeWidth: 5 / scale,
            lineCap: 'round',
            listening: false
          }"
        />
        
        <!-- Label for Cabinet -->
        <v-text
          v-if="cabinetPoints.length >= 4"
          :config="{
            x: cabinetPoints[0],
            y: cabinetPoints[1] - (20 / scale),
            text: cabinet?.name || 'Lemari',
            fontSize: 14 / scale,
            fontStyle: 'bold',
            fill: '#ea580c',
            stroke: '#fff',
            strokeWidth: 2 / scale,
            fillAfterStrokeEnabled: true,
            listening: false
          }"
        />
      </v-layer>
    </v-stage>

    <!-- Controls Overlay -->
    <div v-if="imageLoaded" class="absolute bottom-4 right-4 flex flex-col gap-2 pointer-events-none">
      <div class="flex flex-col bg-white/90 backdrop-blur-sm border border-slate-200 rounded-lg shadow-sm p-1 pointer-events-auto">
        <Button icon="pi pi-plus" severity="secondary" text size="small" @click="zoomIn" v-tooltip.left="'Zoom In'" />
        <div class="h-px bg-slate-100 mx-2"></div>
        <Button icon="pi pi-minus" severity="secondary" text size="small" @click="zoomOut" v-tooltip.left="'Zoom Out'" />
        <div class="h-px bg-slate-100 mx-2"></div>
        <Button icon="pi pi-arrows-alt" severity="secondary" text size="small" @click="fitToView" v-tooltip.left="'Fit View'" />
      </div>
    </div>
    
    <!-- Legend/Info -->
    <div v-if="imageLoaded" class="absolute top-4 left-4 bg-white/80 backdrop-blur-sm border border-slate-200 rounded-lg shadow-sm px-3 py-2 pointer-events-none flex flex-col gap-1.5">
        <div class="flex items-center gap-2">
            <div class="w-3 h-3 rounded-sm bg-orange-500/30 border border-orange-500"></div>
            <span class="text-[10px] font-bold text-slate-700 uppercase">Lokasi Lemari</span>
        </div>
        <div class="flex items-center gap-2">
            <div class="w-3 h-1 rounded-full bg-red-500"></div>
            <span class="text-[10px] font-bold text-slate-700 uppercase">Posisi Pintu</span>
        </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import ProgressSpinner from 'primevue/progressspinner'
import Button from 'primevue/button'

const props = defineProps({
  floor: Object,
  room: Object,
  cabinet: Object
})

const apiBase = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000'
const appBase = import.meta.env.VITE_API_URL || apiBase.replace(/\/api\/v1\/?$/, '').replace(/\/api\/?$/, '')

const containerRef = ref(null)
const stageRef = ref(null)
const floorImage = ref(null)
const imageLoaded = ref(false)
const loadError = ref(false)
const errorMessage = ref('')

// Stage state
const scale = ref(1)
const offsetX = ref(0)
const offsetY = ref(0)
const stageConfig = reactive({
  width: 0,
  height: 0
})

const imgDisplayWidth = ref(0)
const imgDisplayHeight = ref(0)

// Panning state
const isPanning = ref(false)
const lastPointerPosition = ref({ x: 0, y: 0 })

// Constants
const SCALE_STEP = 1.2
const MIN_SCALE = 0.1
const MAX_SCALE = 10

// Computed configs
const layerConfig = computed(() => ({
  x: offsetX.value,
  y: offsetY.value,
  scaleX: scale.value,
  scaleY: scale.value
}))

const imageConfig = computed(() => ({
  image: floorImage.value,
  width: imgDisplayWidth.value,
  height: imgDisplayHeight.value
}))

// Polygon processing
const scalePolygonPoints = (points) => {
  if (!points || !Array.isArray(points)) return []
  
  return points.flatMap(p => {
    // Check if normalized (0-1)
    if (Math.abs(p.x) <= 1.2 && Math.abs(p.y) <= 1.2) {
      return [p.x * imgDisplayWidth.value, p.y * imgDisplayHeight.value]
    }
    // Assume it's legacy fixed-scale (450px height)
    const scaleFactor = imgDisplayHeight.value / 450
    return [p.x * scaleFactor, p.y * scaleFactor]
  })
}

const roomPoints = computed(() => scalePolygonPoints(props.room?.points))
const cabinetPoints = computed(() => scalePolygonPoints(props.cabinet?.points))

// Interaction methods
const handleMouseDown = (e) => {
  const stage = e.target.getStage()
  const pos = stage.getPointerPosition()
  if (!pos) return
  
  isPanning.value = true
  lastPointerPosition.value = { ...pos }
}

const handleMouseMove = (e) => {
  if (!isPanning.value) return
  
  const stage = e.target.getStage()
  const pos = stage.getPointerPosition()
  if (!pos) return
  
  const dx = pos.x - lastPointerPosition.value.x
  const dy = pos.y - lastPointerPosition.value.y
  
  offsetX.value += dx
  offsetY.value += dy
  
  lastPointerPosition.value = { ...pos }
}

const handleMouseUp = () => {
  isPanning.value = false
}

const handleWheel = (e) => {
  e.evt.preventDefault()
  
  const stage = stageRef.value.getStage()
  const oldScale = scale.value
  const pointer = stage.getPointerPosition()
  
  const mousePointTo = {
    x: (pointer.x - offsetX.value) / oldScale,
    y: (pointer.y - offsetY.value) / oldScale,
  }
  
  const direction = e.evt.deltaY < 0 ? 1 : -1
  const newScale = direction > 0 ? oldScale * SCALE_STEP : oldScale / SCALE_STEP
  
  if (newScale < MIN_SCALE || newScale > MAX_SCALE) return
  
  scale.value = newScale
  offsetX.value = pointer.x - mousePointTo.x * newScale
  offsetY.value = pointer.y - mousePointTo.y * newScale
}

const zoomIn = () => {
  const oldScale = scale.value
  const newScale = oldScale * SCALE_STEP
  if (newScale > MAX_SCALE) return
  
  // Zoom toward center of container
  const centerX = stageConfig.width / 2
  const centerY = stageConfig.height / 2
  
  const pointTo = {
    x: (centerX - offsetX.value) / oldScale,
    y: (centerY - offsetY.value) / oldScale
  }
  
  scale.value = newScale
  offsetX.value = centerX - pointTo.x * newScale
  offsetY.value = centerY - pointTo.y * newScale
}

const zoomOut = () => {
  const oldScale = scale.value
  const newScale = oldScale / SCALE_STEP
  if (newScale < MIN_SCALE) return
  
  const centerX = stageConfig.width / 2
  const centerY = stageConfig.height / 2
  
  const pointTo = {
    x: (centerX - offsetX.value) / oldScale,
    y: (centerY - offsetY.value) / oldScale
  }
  
  scale.value = newScale
  offsetX.value = centerX - pointTo.x * newScale
  offsetY.value = centerY - pointTo.y * newScale
}

const fitToView = () => {
  if (!imgDisplayWidth.value) return
  
  const containerW = containerRef.value.clientWidth
  const containerH = containerRef.value.clientHeight
  
  const scaleW = containerW / imgDisplayWidth.value
  const scaleH = containerH / imgDisplayHeight.value
  
  scale.value = Math.min(scaleW, scaleH) * 0.9 // 90% fit
  
  offsetX.value = (containerW - imgDisplayWidth.value * scale.value) / 2
  offsetY.value = (containerH - imgDisplayHeight.value * scale.value) / 2
}

// Auto-focus logic: Zoom and center on the cabinet
const focusOnCabinet = () => {
  const points = cabinetPoints.value
  if (!points || points.length < 4) {
    fitToView()
    return
  }
  
  // Calculate bounding box of cabinet
  let minX = Infinity, maxX = -Infinity, minY = Infinity, maxY = -Infinity
  for (let i = 0; i < points.length; i += 2) {
    minX = Math.min(minX, points[i])
    maxX = Math.max(maxX, points[i])
    minY = Math.min(minY, points[i+1])
    maxY = Math.max(maxY, points[i+1])
  }
  
  const cabWidth = maxX - minX
  const cabHeight = maxY - minY
  const centerX = minX + cabWidth / 2
  const centerY = minY + cabHeight / 2
  
  const containerW = containerRef.value.clientWidth
  const containerH = containerRef.value.clientHeight
  
  // Target scale: make cabinet take up about 40% of the view
  const targetScale = Math.min(containerW / cabWidth, containerH / cabHeight) * 0.4
  scale.value = Math.max(0.5, Math.min(targetScale, 2.0)) // Clamp scale
  
  offsetX.value = containerW / 2 - centerX * scale.value
  offsetY.value = containerH / 2 - centerY * scale.value
}

// Image loading
const loadImage = () => {
  const imagePath = props.floor?.floor_plan_image
  
  if (!props.floor) {
    loadError.value = true
    errorMessage.value = 'Data lantai tidak ditemukan untuk arsip ini.'
    return
  }

  if (!imagePath) {
    loadError.value = true
    errorMessage.value = 'File denah lantai belum diunggah untuk lantai ini.'
    return
  }
  
  const url = imagePath.startsWith('http') ? imagePath : `${appBase}/storage/${imagePath}`
  
  imageLoaded.value = false
  loadError.value = false
  const img = new window.Image()
  img.src = url
  img.onload = () => {
    floorImage.value = img
    
    // Calculate display dimensions keeping aspect ratio
    const containerW = containerRef.value.clientWidth
    const containerH = containerRef.value.clientHeight
    const imgRatio = img.width / img.height
    
    // We use a base width/height for normalized coordinates
    // If the image is loaded, we set its display size to match the container's base
    // But we need to maintain aspect ratio for the visualizer to look correct
    imgDisplayWidth.value = containerW
    imgDisplayHeight.value = containerW / imgRatio
    
    if (imgDisplayHeight.value > containerH * 2) {
        // Extreme aspect ratio case, maybe floor is very long
    }

    stageConfig.width = containerW
    stageConfig.height = containerH
    
    imageLoaded.value = true
    
    // Ensure container size is updated before focusing
    setTimeout(() => {
        if (containerRef.value) {
            stageConfig.width = containerRef.value.clientWidth
            stageConfig.height = containerRef.value.clientHeight
            focusOnCabinet()
        }
    }, 100)
  }
  img.onerror = () => {
      console.error('Failed to load floor plan image:', url)
      loadError.value = true
      errorMessage.value = 'Gagal memuat gambar denah. Pastikan file tersedia di server.'
  }
}

// Lifecycle & Watchers
const resizeObserver = new ResizeObserver(() => {
    if (containerRef.value) {
        stageConfig.width = containerRef.value.clientWidth
        stageConfig.height = containerRef.value.clientHeight
    }
})

onMounted(() => {
  loadImage()
  if (containerRef.value) {
      resizeObserver.observe(containerRef.value)
  }
})

onUnmounted(() => {
    resizeObserver.disconnect()
})

watch(() => props.floor?.floor_plan_image, () => {
  loadImage()
})

watch(() => props.cabinet?.id, () => {
    if (imageLoaded.value) {
        focusOnCabinet()
    }
})
</script>

<style scoped>
.cursor-grab {
  cursor: grab;
}
.cursor-grab:active {
  cursor: grabbing;
}
</style>
