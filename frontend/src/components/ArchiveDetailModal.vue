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

                <div v-if="hasPhysicalLocation" class="mt-auto pt-4 border-t border-slate-200">
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
                        Arsip ini memerlukan persetujuan PIC. Silakan masukkan kode OTP untuk membuka akses.
                    </p>

                    <div v-if="!otpSent">
                        <Button label="Request Akses / OTP" icon="pi pi-send" @click="handleRequestOtp" :loading="loading" />
                    </div>

                    <div v-else class="flex flex-col items-center gap-4 animate-fade-in">
                        <p class="text-sm font-semibold text-blue-600">OTP telah dikirim!</p>
                        <InputOtp v-model="otpCode" :length="6" integerOnly />
                        <div class="flex gap-2">
                            <Button label="Verifikasi" icon="pi pi-check" @click="handleVerifyOtp" :loading="loading" :disabled="otpCode.length < 6" />
                            <Button label="Resend" severity="secondary" text @click="handleRequestOtp" :disabled="loading" />
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
                        <div class="flex-1 bg-white border border-slate-200 rounded-lg overflow-hidden relative flex items-center justify-center">
                            <p class="text-slate-400 italic">[Visualisasi Denah: {{ archive.floor?.name }} > {{ archive.room?.name }}]</p>
                            <!-- Future: Integrasi Konva.js Visualizer -->
                        </div>
                        <div class="mt-4 p-3 bg-blue-50 border border-blue-100 rounded text-sm text-blue-800">
                            <strong>Lokasi Detail:</strong> Lemari {{ archive.cabinet?.name }}, Slot Baris {{ archive.cabinet_slot?.row_position }} Kolom {{ archive.cabinet_slot?.column_position }}
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
import { previewArchive, downloadArchive, requestOtp, verifyOtp } from '@/api/archiveApi'
import { useToast } from 'primevue/usetoast'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import InputOtp from 'primevue/inputotp'
import ProgressSpinner from 'primevue/progressspinner'

const props = defineProps({
    modelValue: Boolean,
    archive: Object
})

const emit = defineEmits(['update:modelValue'])
const toast = useToast()

const visible = computed({
    get: () => props.modelValue,
    set: (val) => emit('update:modelValue', val)
})

const viewMode = ref('preview') // 'preview' | 'location'
const isUnlocked = ref(false)
const otpSent = ref(false)
const otpCode = ref('')
const loading = ref(false)
const loadingPreview = ref(false)
const previewUrl = ref(null)

// Computed
const needsAccess = computed(() => {
    return props.archive?.download_policy === 'request_to_pic' && !isUnlocked.value
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
watch(() => props.archive, (newVal) => {
    if (newVal) {
        resetState()
        if (newVal.download_policy === 'direct_download') {
            loadPreview()
        }
    }
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

const handleRequestOtp = async () => {
    loading.value = true
    try {
        await requestOtp(props.archive.id)
        otpSent.value = true
        toast.add({ severity: 'info', summary: 'OTP Terkirim', detail: 'Silakan cek perangkat Anda.', life: 3000 })
    } catch (err) {
        toast.add({ severity: 'error', summary: 'Gagal', detail: 'Gagal mengirim OTP.', life: 3000 })
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
