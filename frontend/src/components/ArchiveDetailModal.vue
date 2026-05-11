<template>
    <Dialog 
        v-model:visible="visible" 
        modal 
        :style="{ width: '90vw', maxWidth: '1200px' }"
        :breakpoints="{ '960px': '95vw' }"
        :maximized="isMobile"
        class="archive-detail-dialog"
        @hide="onClose"
        :showHeader="true"
    >
        <template #header>
            <div class="flex items-center justify-between w-full pr-8 md:pr-0">
                <h3 class="text-base md:text-xl font-bold text-slate-800 truncate">
                    Detail Arsip: {{ archive?.name }}
                </h3>
            </div>
        </template>
        <div v-if="archive" class="grid grid-cols-12 gap-6 min-h-[500px]">
            <!-- Left Column: Details Sidebar -->
            <div class="col-span-12 lg:col-span-4 flex flex-col gap-5 bg-slate-50/50 p-5 rounded-xl border border-slate-100">
                <div class="flex items-center gap-2 mb-2">
                    <i class="pi pi-info-circle text-blue-500"></i>
                    <h3 class="text-sm font-bold text-slate-800 uppercase tracking-tight">Informasi Arsip</h3>
                </div>

                <!-- Checkout Status Badge -->
                <div class="flex items-center gap-2 mb-4">
                    <Tag 
                        :value="archive.is_checked_out ? 'Sedang di Luar' : 'Tersedia'" 
                        :severity="archive.is_checked_out ? 'danger' : 'success'"
                        class="text-sm px-3 py-1"
                    />
                    <span v-if="archive.checked_out_at" class="text-[10px] text-slate-400">
                        {{ formatDateTimeShort(archive.checked_out_at) }}
                    </span>
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

                <!-- Checkout Action Buttons -->
                <div v-if="canModifyCheckout" class="flex flex-col gap-2 pt-4 border-t border-slate-200">
                    <Button 
                        v-if="!archive.is_checked_out"
                        label="Keluarkan Arsip" 
                        icon="pi pi-external-link" 
                        severity="warning" 
                        outlined 
                        class="w-full"
                        @click="showCheckoutDialog = true"
                    />
                    <Button 
                        v-else
                        label="Tandai Sudah Kembali" 
                        icon="pi pi-check-circle" 
                        severity="success" 
                        outlined 
                        class="w-full"
                        @click="confirmCheckin"
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
                    <div v-else-if="viewMode === 'location'" class="w-full h-[350px] md:h-full flex flex-col p-2 md:p-4 animate-fade-in">
                        <div class="flex-1 bg-white border border-slate-200 rounded-lg overflow-hidden relative shadow-inner">
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
                            <h3 class="text-base font-bold text-slate-700 uppercase tracking-tight">Riwayat Arsip</h3>
                        </div>

                        <div v-if="loadingHistory" class="flex flex-col items-center justify-center py-20">
                            <ProgressSpinner style="width: 40px; height: 40px" />
                            <p class="mt-3 text-xs text-slate-400">Memuat riwayat...</p>
                        </div>

                        <Timeline v-else-if="combinedHistory.length > 0" :value="combinedHistory" class="customized-timeline">
                            <template #marker="slotProps">
                                <span class="flex items-center justify-center w-8 h-8 rounded-full shadow-sm border-2" :class="{
                                    'bg-blue-50 border-blue-200 text-blue-500': slotProps.item.event_type === 'location_move',
                                    'bg-orange-50 border-orange-200 text-orange-500': slotProps.item.event_type === 'checkout',
                                    'bg-green-50 border-green-200 text-green-500': slotProps.item.event_type === 'checkin'
                                }">
                                    <i :class="{
                                        'pi pi-box': slotProps.item.event_type === 'location_move',
                                        'pi pi-external-link': slotProps.item.event_type === 'checkout',
                                        'pi pi-check-circle': slotProps.item.event_type === 'checkin'
                                    }" class="text-sm"></i>
                                </span>
                            </template>
                            <template #content="slotProps">
                                <div class="flex flex-col mb-6 ml-2 bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                                    <!-- Card Header -->
                                    <div class="flex items-center justify-between px-4 py-2 border-b border-slate-50 bg-slate-50/30">
                                        <span class="text-[10px] font-black uppercase tracking-widest" :class="{
                                            'text-blue-600': slotProps.item.event_type === 'location_move',
                                            'text-orange-600': slotProps.item.event_type === 'checkout',
                                            'text-green-600': slotProps.item.event_type === 'checkin'
                                        }">
                                            {{ slotProps.item.event_type === 'location_move' ? 'Pindah Lokasi' : 
                                               slotProps.item.event_type === 'checkout' ? 'Dikeluarkan' : 'Dikembalikan' }}
                                        </span>
                                        <span class="text-[10px] font-medium text-slate-400">
                                            {{ formatDateTimeShort(slotProps.item.created_at) }}
                                        </span>
                                    </div>

                                    <!-- Card Body -->
                                    <div class="p-4">
                                        <!-- Location Move Event -->
                                        <div v-if="slotProps.item.event_type === 'location_move'">
                                            <div class="flex flex-col gap-3">
                                                <!-- From -->
                                                <div v-if="slotProps.item.old_floor" class="flex gap-3">
                                                    <div class="flex flex-col items-center">
                                                        <div class="w-1.5 h-1.5 rounded-full bg-slate-300 mt-1"></div>
                                                        <div class="w-px h-full bg-slate-200 my-1"></div>
                                                    </div>
                                                    <div class="flex flex-col">
                                                        <span class="text-[9px] uppercase font-bold text-slate-400">Dari</span>
                                                        <span class="text-xs text-slate-500">{{ slotProps.item.old_floor?.name }} > {{ slotProps.item.old_room?.name }}</span>
                                                        <span class="text-[10px] text-slate-400">Lemari {{ slotProps.item.old_cabinet?.name }}, Slot {{ slotProps.item.old_cabinet_slot?.name }}</span>
                                                    </div>
                                                </div>
                                                <div v-else class="text-[11px] text-slate-400 italic flex items-center gap-2 mb-1">
                                                    <i class="pi pi-plus-circle text-[10px]"></i> Penempatan Pertama
                                                </div>

                                                <!-- To -->
                                                <div class="flex gap-3">
                                                    <div class="flex flex-col items-center">
                                                        <div class="w-1.5 h-1.5 rounded-full bg-blue-500 mt-1"></div>
                                                    </div>
                                                    <div class="flex flex-col">
                                                        <span class="text-[9px] uppercase font-bold text-blue-500">Ke</span>
                                                        <span class="text-xs font-bold text-slate-700">{{ slotProps.item.new_floor?.name }} > {{ slotProps.item.new_room?.name }}</span>
                                                        <span class="text-[10px] text-slate-500 font-medium">Lemari {{ slotProps.item.new_cabinet?.name }}, Slot {{ slotProps.item.new_cabinet_slot?.name }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mt-3 flex items-center gap-2 pt-3 border-t border-slate-50">
                                                <div class="w-5 h-5 rounded-full bg-slate-100 flex items-center justify-center text-[10px] text-slate-500 font-bold">
                                                    {{ slotProps.item.moved_by?.name?.charAt(0) }}
                                                </div>
                                                <span class="text-[10px] text-slate-400">Oleh <span class="text-slate-600 font-medium">{{ slotProps.item.moved_by?.name }}</span></span>
                                            </div>
                                        </div>

                                        <!-- Checkout Event -->
                                        <div v-else-if="slotProps.item.event_type === 'checkout'" class="flex flex-col gap-3">
                                            <div class="grid grid-cols-2 gap-4">
                                                <div class="flex flex-col">
                                                    <span class="text-[9px] uppercase font-bold text-slate-400">Peminjam</span>
                                                    <span class="text-xs font-bold text-slate-700">{{ slotProps.item.borrower_name }}</span>
                                                </div>
                                                <div class="flex flex-col">
                                                    <span class="text-[9px] uppercase font-bold text-slate-400">Rencana Kembali</span>
                                                    <span class="text-xs text-slate-600">{{ formatDate(slotProps.item.planned_return_date) }}</span>
                                                </div>
                                            </div>
                                            <div class="flex flex-col">
                                                <span class="text-[9px] uppercase font-bold text-slate-400">Alasan</span>
                                                <span class="text-xs text-slate-600">{{ slotProps.item.reason }}</span>
                                            </div>
                                            <div class="mt-1 flex items-center gap-2 pt-3 border-t border-slate-50">
                                                <div class="w-5 h-5 rounded-full bg-slate-100 flex items-center justify-center text-[10px] text-slate-500 font-bold">
                                                    {{ slotProps.item.actor_user?.name?.charAt(0) }}
                                                </div>
                                                <span class="text-[10px] text-slate-400">Diproses oleh <span class="text-slate-600 font-medium">{{ slotProps.item.actor_user?.name }}</span></span>
                                            </div>
                                        </div>

                                        <!-- Checkin Event -->
                                        <div v-else-if="slotProps.item.event_type === 'checkin'" class="flex flex-col gap-2">
                                            <div class="flex items-center gap-2 text-green-600">
                                                <i class="pi pi-check-circle text-xs"></i>
                                                <span class="text-xs font-bold">Telah Kembali</span>
                                            </div>
                                            <span class="text-[10px] text-slate-500">Dikembalikan pada {{ formatDate(slotProps.item.actual_return_date) }}</span>
                                            <div class="mt-2 flex items-center gap-2 pt-3 border-t border-slate-50">
                                                <div class="w-5 h-5 rounded-full bg-slate-100 flex items-center justify-center text-[10px] text-slate-500 font-bold">
                                                    {{ slotProps.item.actor_user?.name?.charAt(0) }}
                                                </div>
                                                <span class="text-[10px] text-slate-400">Oleh <span class="text-slate-600 font-medium">{{ slotProps.item.actor_user?.name }}</span></span>
                                            </div>
                                        </div>

                                        <!-- Global Notes -->
                                        <div v-if="slotProps.item.notes" class="mt-3 bg-slate-50 p-2.5 rounded-lg text-[10px] text-slate-500 border-l-4 border-slate-200">
                                            <i class="pi pi-info-circle mr-1 opacity-50"></i>
                                            "{{ slotProps.item.notes }}"
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </Timeline>

                        <div v-else class="flex flex-col items-center justify-center py-20 text-slate-300">
                            <i class="pi pi-history text-5xl mb-4 opacity-20"></i>
                            <p class="italic">Belum ada riwayat.</p>
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

            <!-- Mobile Sticky Bottom Bar -->
            <div v-if="isMobile && !needsAccess" class="fixed bottom-16 left-0 right-0 p-4 bg-white border-t border-slate-200 flex gap-2 z-50">
                <Button 
                    v-if="canDownload"
                    label="Download" 
                    icon="pi pi-download" 
                    severity="success" 
                    class="flex-1 !rounded-xl"
                    @click="handleDownload" 
                />
                <Button 
                    v-if="canModifyCheckout"
                    :label="archive.is_checked_out ? 'Kembalikan' : 'Keluarkan'" 
                    :icon="archive.is_checked_out ? 'pi pi-check-circle' : 'pi pi-external-link'" 
                    severity="warning" 
                    class="flex-1 !rounded-xl"
                    @click="archive.is_checked_out ? confirmCheckin() : (showCheckoutDialog = true)"
                />
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

        <!-- Checkout Dialog -->
        <CheckoutDialog 
            v-model="showCheckoutDialog" 
            :archive="archive" 
            @checked-out="handleCheckedOut"
        />

        <!-- Confirm Dialog for Checkin -->
        <ConfirmDialog />
    </Dialog>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { previewArchive, downloadArchive, requestOtp, verifyOtp, fetchArchiveLocationHistories } from '@/api/archiveApi'
import { checkinArchive, getCheckoutHistory } from '@/api/archiveCheckoutApi'
import { useAuthStore } from '@/store/auth'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import InputOtp from 'primevue/inputotp'
import ProgressSpinner from 'primevue/progressspinner'
import Timeline from 'primevue/timeline'
import ConfirmDialog from 'primevue/confirmdialog'
import LocationVisualizer from '@/components/LocationVisualizer.vue'
import CheckoutDialog from '@/components/CheckoutDialog.vue'

// Mobile Check
const windowWidth = ref(window.innerWidth)
const updateWidth = () => { windowWidth.value = window.innerWidth }
onMounted(() => window.addEventListener('resize', updateWidth))
onUnmounted(() => window.removeEventListener('resize', updateWidth))
const isMobile = computed(() => windowWidth.value < 768)

const props = defineProps({
    modelValue: Boolean,
    archive: Object,
    alreadyUnlocked: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['update:modelValue', 'archive-updated'])
const toast = useToast()
const authStore = useAuthStore()
const confirm = useConfirm()

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
const checkoutHistories = ref([])
const combinedHistory = ref([])
const loadingHistory = ref(false)
const showCheckoutDialog = ref(false)

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

const canModifyCheckout = computed(() => {
    const user = authStore.user
    if (!user || !props.archive) return false
    
    const isPic = props.archive.pic_user_id === user.id
    const isAdminOrRoot = ['admin', 'root'].includes(user.role)
    
    return isPic || isAdminOrRoot
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
        const [locationRes, checkoutRes] = await Promise.all([
            fetchArchiveLocationHistories(props.archive.id),
            getCheckoutHistory(props.archive.id)
        ])
        
        locationHistories.value = locationRes.data.data
        checkoutHistories.value = checkoutRes.data.data
        
        // Combine and sort by created_at
        const locationEvents = locationHistories.value.map(item => ({
            ...item,
            event_type: 'location_move'
        }))
        
        const checkoutEvents = checkoutHistories.value.map(item => ({
            ...item,
            event_type: item.action // 'checkout' or 'checkin'
        }))
        
        combinedHistory.value = [...locationEvents, ...checkoutEvents].sort((a, b) => 
            new Date(b.created_at) - new Date(a.created_at)
        )
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

const handleCheckedOut = (updatedArchive) => {
    emit('archive-updated', updatedArchive)
}

const confirmCheckin = () => {
    confirm.require({
        message: 'Apakah Anda yakin ingin menandai arsip ini sudah kembali?',
        header: 'Konfirmasi Pengembalian',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Ya, Tandai Kembali',
        rejectLabel: 'Batal',
        accept: () => handleCheckin()
    })
}

const handleCheckin = async () => {
    try {
        const res = await checkinArchive(props.archive.id)
        toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Arsip berhasil ditandai kembali', life: 3000 })
        emit('archive-updated', res.data.data)
    } catch (err) {
        toast.add({ severity: 'error', summary: 'Gagal', detail: err.response?.data?.message || 'Gagal menandai pengembalian', life: 3000 })
    }
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

/* Timeline Custom Styles */
.customized-timeline :deep(.p-timeline-event-opposite) {
    display: none;
}
.customized-timeline :deep(.p-timeline-event-content) {
    padding-left: 0.5rem;
}
.customized-timeline :deep(.p-timeline-event-separator) {
    align-items: center;
}
.customized-timeline :deep(.p-timeline-event-connector) {
    width: 2px;
    background-color: #f1f5f9;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
