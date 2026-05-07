<template>
    <Dialog 
        v-model:visible="visible" 
        modal 
        :header="`Detail Arsip: ${archive?.name || ''}`" 
        :style="{ width: '90vw', maxWidth: '1200px' }"
        :breakpoints="{ '960px': '95vw' }"
        class="archive-detail-dialog"
        @hide="onClose"
    >
        <div v-if="archive" class="grid grid-cols-12 gap-6 min-h-[500px]">
            <!-- Left Column: Details Sidebar -->
            <div class="col-span-12 lg:col-span-4 flex flex-col gap-5 bg-slate-50/50 p-5 rounded-xl border border-slate-100">
                <div class="flex items-center gap-2 mb-2">
                    <i class="pi pi-info-circle text-blue-500"></i>
                    <h3 class="text-sm font-bold text-slate-800 uppercase tracking-tight">Informasi Arsip</h3>
                </div>

                <div class="flex flex-col gap-5">
                    <!-- Basic Info Group -->
                    <div class="flex flex-col gap-3">
                        <div class="detail-item">
                            <label><i class="pi pi-file text-[10px] mr-1"></i> Nama Dokumen</label>
                            <p class="text-lg leading-tight">{{ archive.name }}</p>
                        </div>
                        <div class="detail-item">
                            <label><i class="pi pi-id-card text-[10px] mr-1"></i> No. Berkas</label>
                            <p>{{ archive.file_number || '-' }}</p>
                        </div>
                    </div>

                    <div class="h-px bg-slate-200"></div>

                    <!-- Organization & Type Group -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="detail-item">
                            <label><i class="pi pi-building text-[10px] mr-1"></i> PT</label>
                            <p>{{ archive.company?.name }}</p>
                        </div>
                        <div class="detail-item">
                            <label><i class="pi pi-tag text-[10px] mr-1"></i> Tipe</label>
                            <p class="capitalize">{{ archive.archive_type?.replace('_', ' ') }}</p>
                        </div>
                    </div>

                    <div class="detail-item">
                        <label><i class="pi pi-folder-open text-[10px] mr-1"></i> Kategori</label>
                        <p>{{ archive.category?.name }}</p>
                    </div>

                    <div class="h-px bg-slate-200"></div>

                    <!-- Dates Group -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="detail-item">
                            <label><i class="pi pi-calendar text-[10px] mr-1"></i> Tgl Terbit</label>
                            <p>{{ formatDate(archive.issue_date) }}</p>
                        </div>
                        <div class="detail-item">
                            <label><i class="pi pi-calendar-times text-[10px] mr-1"></i> Kadaluarsa</label>
                            <p>{{ archive.expire_date ? formatDate(archive.expire_date) : '-' }}</p>
                        </div>
                    </div>

                    <div class="detail-item">
                        <label><i class="pi pi-shield text-[10px] mr-1"></i> Privacy & Akses</label>
                        <div class="flex items-center gap-2 mt-1">
                            <Tag :value="archive.privacy_type?.toUpperCase()" :severity="getPrivacySeverity(archive.privacy_type)" />
                            <Tag v-if="archive.download_policy" :value="archive.download_policy.replace('_', ' ').toUpperCase()" severity="secondary" />
                        </div>
                    </div>
                </div>

                <div class="detail-item mt-2">
                    <label><i class="pi pi-align-left text-[10px] mr-1"></i> Keterangan</label>
                    <div class="keterangan-box mt-1">
                        {{ archive.keterangan || 'Tidak ada keterangan.' }}
                    </div>
                </div>

                <div class="detail-item">
                    <label><i class="pi pi-hashtag text-[10px] mr-1"></i> Hashtag</label>
                    <div class="flex flex-wrap gap-1.5 mt-1">
                        <Tag v-for="tag in archive.tags" :key="tag.id" :value="tag.nama" severity="secondary" rounded />
                        <span v-if="!archive.tags?.length" class="text-slate-400 italic text-xs">Tidak ada hashtag.</span>
                    </div>
                </div>

                <div v-if="hasPhysicalLocation" class="mt-auto flex flex-col gap-2 pt-4 border-t border-slate-200">
                    <Button 
                        :label="viewMode === 'history' ? 'Lihat Preview File' : 'Riwayat Lokasi'" 
                        icon="pi pi-history" 
                        severity="secondary" 
                        outlined 
                        class="w-full"
                        @click="toggleHistoryMode"
                    />
                    <Button 
                        :label="viewMode === 'location' ? 'Lihat Preview File' : 'Lihat Lokasi Fisik'" 
                        :icon="viewMode === 'location' ? 'pi pi-file' : 'pi pi-map-marker'" 
                        severity="help" 
                        outlined 
                        class="w-full"
                        @click="toggleViewMode"
                    />
                </div>
            </div>

            <!-- Right Column: dynamic Area -->
            <div class="col-span-12 lg:col-span-8 bg-slate-50 rounded-xl relative overflow-hidden flex flex-col items-center justify-center border border-slate-200">
                
                <!-- 1. Need Request / OTP State -->
                <div v-if="needsAccess" class="flex flex-col items-center justify-center p-8 text-center max-w-md">
                    <div class="w-16 h-16 bg-red-100 text-red-500 rounded-full flex items-center justify-center mb-6">
                        <i class="pi pi-lock text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800 mb-2">Akses Terbatas</h3>
                    <p class="text-slate-500 mb-6">
                        Arsip ini memerlukan persetujuan PIC untuk dibuka.<br>
                        Hubungi <span class="font-bold text-slate-700">{{ archive.pic?.name || 'PIC' }}</span> untuk mendapatkan kode OTP.
                    </p>

                    <div class="flex flex-col items-center gap-5 w-full">
                        <div class="flex flex-col items-center gap-3 w-full bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                            <label class="text-sm font-semibold text-slate-600">Masukkan Kode OTP</label>
                            <InputOtp v-model="otpCode" :length="6" integerOnly />
                            <Button 
                                label="Verifikasi & Buka Akses" 
                                icon="pi pi-check" 
                                class="w-full mt-2"
                                @click="handleVerifyOtp" 
                                :loading="loading" 
                                :disabled="!otpCode || String(otpCode).length < 6" 
                            />
                        </div>

                        <div class="flex flex-col items-center gap-2">
                            <p class="text-xs text-slate-400">Belum punya kode OTP?</p>
                            <Button 
                                :label="otpSent ? 'Kirim Ulang Request' : 'Request OTP ke PIC'" 
                                :icon="otpSent ? 'pi pi-refresh' : 'pi pi-send'" 
                                severity="secondary" 
                                text 
                                size="small"
                                @click="handleRequestOtp" 
                                :loading="loading" 
                            />
                            <p v-if="otpSent" class="text-[10px] text-blue-500 font-medium animate-pulse">
                                Request telah dikirim! Silakan hubungi PIC.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- 2. Content Unlocked -->
                <template v-else>
                    <!-- Loading State for Preview -->
                    <div v-if="loadingPreview" class="flex flex-col items-center">
                        <ProgressSpinner style="width: 40px; height: 40px" />
                        <p class="mt-2 text-slate-400 text-sm">Memuat konten...</p>
                    </div>

                    <!-- Location Mode -->
                    <div v-else-if="viewMode === 'location'" class="w-full h-full flex flex-col p-4 animate-fade-in">
                        <div class="flex-1 bg-white border border-slate-200 rounded-lg overflow-hidden relative">
                            <LocationVisualizer 
                                :floor="archive.floor" 
                                :room="archive.room" 
                                :cabinet="archive.cabinet" 
                            />
                        </div>
                        <div class="mt-4 p-3 bg-blue-50 border border-blue-100 rounded text-sm text-blue-800">
                            <strong>Lokasi Detail:</strong> Lemari {{ archive.cabinet?.name }}, Slot {{ archive.cabinet_slot?.name }}
                        </div>
                    </div>

                    <!-- History Mode -->
                    <div v-else-if="viewMode === 'history'" class="w-full h-full flex flex-col p-6 animate-fade-in overflow-y-auto bg-white">
                        <div class="flex items-center gap-2 mb-6 border-b border-slate-100 pb-4">
                            <i class="pi pi-history text-blue-500"></i>
                            <h3 class="text-base font-bold text-slate-700 uppercase tracking-tight">Riwayat Pergerakan Fisik</h3>
                        </div>

                        <div v-if="loadingHistory" class="flex flex-col items-center justify-center py-20">
                            <ProgressSpinner style="width: 40px; height: 40px" />
                            <p class="mt-3 text-xs text-slate-400">Memuat riwayat...</p>
                        </div>

                        <Timeline v-else-if="locationHistories.length > 0" :value="locationHistories" class="customized-timeline">
                            <template #opposite="slotProps">
                                <small class="text-slate-400 font-medium whitespace-nowrap">{{ formatDateTimeShort(slotProps.item.created_at) }}</small>
                            </template>
                            <template #content="slotProps">
                                <div class="flex flex-col mb-6 bg-slate-50/50 p-3 rounded-xl border border-slate-100 shadow-sm">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="text-[10px] font-bold px-1.5 py-0.5 bg-blue-50 text-blue-600 rounded">DIPINDAHKAN</span>
                                        <span class="text-[10px] text-slate-400 italic">oleh {{ slotProps.item.moved_by?.name }}</span>
                                    </div>
                                    <div class="text-xs text-slate-600">
                                        <!-- Lokasi Lama -->
                                        <div v-if="slotProps.item.old_floor" class="flex flex-col mb-2 pb-2 border-b border-slate-100/50">
                                            <div class="flex items-center gap-1.5 mb-0.5">
                                                <i class="pi pi-history text-[10px] text-slate-300"></i>
                                                <span class="text-slate-400">Dari: {{ slotProps.item.old_floor?.name }} > {{ slotProps.item.old_room?.name }}</span>
                                            </div>
                                            <div class="pl-4 text-[10px] text-slate-300">
                                                Lemari {{ slotProps.item.old_cabinet?.name }}
                                                <span v-if="slotProps.item.old_cabinet_slot">, Slot {{ slotProps.item.old_cabinet_slot?.name }}</span>
                                            </div>
                                        </div>
                                        <div v-else class="flex items-center gap-1.5 mb-2 text-slate-400 italic">
                                            <i class="pi pi-plus-circle text-[10px]"></i>
                                            <span>Penempatan Lokasi Pertama</span>
                                        </div>

                                        <!-- Lokasi Baru -->
                                        <div class="flex items-center gap-1.5 mb-1">
                                            <i class="pi pi-map-marker text-[10px] text-blue-400"></i>
                                            <span class="font-medium">Ke: {{ slotProps.item.new_floor?.name }} > {{ slotProps.item.new_room?.name }}</span>
                                        </div>
                                        <div class="pl-4 text-[10px] text-slate-500">
                                            Lemari {{ slotProps.item.new_cabinet?.name }}
                                            <span v-if="slotProps.item.new_cabinet_slot">, Slot {{ slotProps.item.new_cabinet_slot?.name }}</span>
                                        </div>
                                    </div>
                                    <div v-if="slotProps.item.notes" class="mt-2 text-[11px] text-slate-500 bg-white p-2 rounded border-l-2 border-slate-200 italic">
                                        "{{ slotProps.item.notes }}"
                                    </div>
                                </div>
                            </template>
                        </Timeline>

                        <div v-else class="flex flex-col items-center justify-center py-20 text-slate-300">
                            <i class="pi pi-history text-5xl mb-4 opacity-20"></i>
                            <p class="italic">Belum ada riwayat pemindahan.</p>
                        </div>
                    </div>

                    <!-- Preview Mode -->
                    <div v-else class="w-full h-full flex flex-col animate-fade-in">
                        <!-- PDF Preview -->
                        <iframe v-if="isPdf" :src="previewUrl" class="w-full h-full border-none rounded-xl"></iframe>
                        
                        <!-- Image Preview -->
                        <div v-else-if="isImage" class="w-full h-full flex items-center justify-center p-4">
                            <img :src="previewUrl" class="max-w-full max-h-full object-contain shadow-lg rounded-lg shadow-slate-200" />
                        </div>

                        <!-- Other types -->
                        <div v-else class="flex flex-col items-center justify-center h-full text-slate-400">
                            <i class="pi pi-file-excel text-5xl mb-4"></i>
                            <p>Preview tidak tersedia untuk format ini.</p>
                            <Button label="Download untuk Melihat" icon="pi pi-download" class="mt-4" severity="secondary" @click="handleDownload" />
                        </div>
                    </div>
                </template>

            </div>
        </div>

        <template #footer>
            <Button label="Tutup" icon="pi pi-times" severity="secondary" text @click="visible = false" />
            <Button 
                v-if="canDownload" 
                label="Download" 
                icon="pi pi-download" 
                severity="success" 
                @click="handleDownload" 
                :disabled="needsAccess"
            />
        </template>
    </Dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { previewArchive, downloadArchive, requestOtp, verifyOtp, fetchArchiveLocationHistories } from '@/api/archiveApi'
import { useAuthStore } from '@/store/auth'
import { useToast } from 'primevue/usetoast'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import InputOtp from 'primevue/inputotp'
import ProgressSpinner from 'primevue/progressspinner'
import Timeline from 'primevue/timeline'
import LocationVisualizer from '@/components/LocationVisualizer.vue'

const props = defineProps({
    modelValue: Boolean,
    archive: Object,
    alreadyUnlocked: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['update:modelValue'])
const toast = useToast()
const authStore = useAuthStore()

const visible = computed({
    get: () => props.modelValue,
    set: (val) => emit('update:modelValue', val)
})

const viewMode = ref('preview') // 'preview' | 'location' | 'history'
const isUnlocked = ref(false)
const otpSent = ref(false)
const otpCode = ref('')
const loading = ref(false)
const loadingPreview = ref(false)
const previewUrl = ref(null)

const locationHistories = ref([])
const loadingHistory = ref(false)

// Computed
const needsAccess = computed(() => {
    if (!props.archive) return false
    
    // Admin or PIC always has access
    const user = authStore.user
    if (user?.role === 'admin' || props.archive.pic_user_id === user?.id || props.archive.created_by === user?.id) {
        return false
    }

    // Check if already unlocked from parent component
    if (props.alreadyUnlocked) return false

    return props.archive.download_policy === 'request_to_pic' && !isUnlocked.value
})

const hasPhysicalLocation = computed(() => {
    return props.archive?.floor_id && props.archive?.room_id && props.archive?.cabinet_id
})

const canDownload = computed(() => {
    return props.archive?.archive_type !== 'placeholder'
})

const isPdf = computed(() => {
    const type = props.archive?.file_type?.toLowerCase()
    return type === 'pdf'
})

const isImage = computed(() => {
    const type = props.archive?.file_type?.toLowerCase()
    return ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(type)
})

// Logic
const initModal = () => {
    if (props.archive) {
        resetState()
        if (!needsAccess.value) {
            loadPreview()
        }
    }
}

watch(() => props.modelValue, (isOpen) => {
    if (isOpen) initModal()
})

watch(() => props.archive, (newVal) => {
    if (props.modelValue && newVal) initModal()
})

const resetState = () => {
    isUnlocked.value = false
    otpSent.value = false
    otpCode.value = ''
    viewMode.value = 'preview'
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value)
        previewUrl.value = null
    }
}

const toggleViewMode = () => {
    viewMode.value = viewMode.value === 'preview' ? 'location' : 'preview'
}

const toggleHistoryMode = () => {
    if (viewMode.value === 'history') {
        viewMode.value = 'preview'
    } else {
        viewMode.value = 'history'
        loadHistory()
    }
}

const loadHistory = async () => {
    loadingHistory.value = true
    try {
        const res = await fetchArchiveLocationHistories(props.archive.id)
        locationHistories.value = res.data.data
    } catch (err) {
        console.error('Failed to load history', err)
    } finally {
        loadingHistory.value = false
    }
}

const handleRequestOtp = async () => {
    loading.value = true
    try {
        const res = await requestOtp(props.archive.id)
        otpSent.value = true
        toast.add({ severity: 'info', summary: 'Permintaan Terkirim', detail: res.data?.message || 'Permintaan OTP sedang diproses oleh PIC.', life: 5000 })
    } catch (err) {
        const msg = err.response?.data?.message || 'Gagal mengirim permintaan OTP.'
        toast.add({ severity: 'warn', summary: 'Tidak Dapat Mengirim', detail: msg, life: 6000 })
    } finally {
        loading.value = false
    }
}

const handleVerifyOtp = async () => {
    loading.value = true
    try {
        await verifyOtp(props.archive.id, otpCode.value)
        isUnlocked.value = true
        toast.add({ severity: 'success', summary: 'Akses Terbuka', detail: 'OTP berhasil diverifikasi.', life: 3000 })
        loadPreview()
    } catch (err) {
        toast.add({ severity: 'error', summary: 'Gagal', detail: 'Kode OTP tidak valid.', life: 3000 })
    } finally {
        loading.value = false
    }
}

const loadPreview = async () => {
    if (props.archive.archive_type === 'placeholder') return
    if (!isPdf.value && !isImage.value) return

    loadingPreview.value = true
    try {
        const res = await previewArchive(props.archive.id)
        previewUrl.value = URL.createObjectURL(res.data)
    } catch (err) {
        console.error('Failed to load preview', err)
    } finally {
        loadingPreview.value = false
    }
}

const handleDownload = async () => {
    try {
        const res = await downloadArchive(props.archive.id)
        const url = URL.createObjectURL(res.data)
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', props.archive.name)
        document.body.appendChild(link)
        link.click()
        link.remove()
        URL.revokeObjectURL(url)
    } catch (err) {
        toast.add({ severity: 'error', summary: 'Gagal', detail: 'Gagal mengunduh file.', life: 3000 })
    }
}

const onClose = () => {
    resetState()
}

// Helpers
const formatDate = (dateString) => {
    if (!dateString) return '-'
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: '2-digit', month: 'short', year: 'numeric'
    })
}

const formatDateTimeShort = (dateString) => {
    if (!dateString) return '-'
    return new Date(dateString).toLocaleString('id-ID', {
        day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit'
    })
}

const getPrivacySeverity = (type) => {
    switch (type) {
        case 'public': return 'success'
        case 'department': return 'info'
        case 'user': return 'warn'
        case 'private': return 'danger'
        default: return 'secondary'
    }
}
</script>

<style scoped>
.archive-detail-dialog :deep(.p-dialog-content) {
    padding-top: 1.5rem;
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
}

.detail-item label {
    font-size: 0.65rem;
    text-transform: uppercase;
    font-weight: 700;
    color: #64748b;
    letter-spacing: 0.05em;
    display: flex;
    align-items: center;
}

.detail-item p {
    font-weight: 600;
    color: #334155;
    margin: 0;
    font-size: 0.9rem;
}

.keterangan-box {
    background: #f8fafc;
    border: 1px solid #f1f5f9;
    padding: 0.75rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    color: #475569;
    white-space: pre-wrap;
    min-height: 80px;
}

.animate-fade-in {
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
