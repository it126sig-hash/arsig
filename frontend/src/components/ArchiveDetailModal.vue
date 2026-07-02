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
                    <Tag
                        v-if="archive.is_confidential"
                        value="CONFIDENTIAL"
                        severity="warn"
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
                            <Tag v-if="archive.is_confidential" value="CONFIDENTIAL" severity="warn" />
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
            <div class="detail-content-panel col-span-12 lg:col-span-8 bg-slate-50 rounded-xl relative overflow-hidden flex flex-col border border-slate-200">

                <!-- 1. Need Request / OTP State -->
                <div v-if="needsAccess" class="flex flex-col items-center justify-center p-8 text-center max-w-md m-auto">
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
                    <div class="archive-tabbar">
                        <button
                            type="button"
                            :class="['archive-tab', { 'archive-tab--active': viewMode === 'preview' }]"
                            :aria-selected="viewMode === 'preview'"
                            @click="activateTab('preview')"
                        >
                            <i class="pi pi-file"></i>
                            <span>File Preview</span>
                        </button>
                        <button
                            type="button"
                            :class="['archive-tab', { 'archive-tab--active': viewMode === 'history' }]"
                            :aria-selected="viewMode === 'history'"
                            @click="activateTab('history')"
                        >
                            <i class="pi pi-history"></i>
                            <span>Riwayat Lokasi</span>
                        </button>
                        <button
                            v-if="canViewLocation"
                            type="button"
                            :class="['archive-tab', { 'archive-tab--active': viewMode === 'location' }]"
                            :aria-selected="viewMode === 'location'"
                            @click="activateTab('location')"
                        >
                            <i class="pi pi-map-marker"></i>
                            <span>Lokasi Fisik</span>
                        </button>
                    </div>

                    <div class="tab-content-region">
                        <!-- Preview Tab -->
                        <div v-if="viewMode === 'preview'" class="w-full h-full flex flex-col animate-fade-in">
                            <div v-if="loadingPreview" class="flex flex-col items-center justify-center h-full">
                                <ProgressSpinner style="width: 40px; height: 40px" />
                                <p class="mt-2 text-slate-400 text-sm">Memuat konten...</p>
                            </div>

                            <iframe v-else-if="isPdf" :src="previewUrl" class="w-full h-full border-none rounded-b-xl"></iframe>

                            <div v-else-if="isImage" class="w-full h-full flex items-center justify-center p-4">
                                <img :src="previewUrl" class="max-w-full max-h-full object-contain shadow-lg rounded-lg shadow-slate-200" />
                            </div>

                            <div v-else class="flex flex-col items-center justify-center h-full text-slate-400 p-8 text-center">
                                <i class="pi pi-file-excel text-5xl mb-4"></i>
                                <p>Preview tidak tersedia untuk format ini.</p>
                                <Button v-if="canDownload" label="Download untuk Melihat" icon="pi pi-download" class="mt-4" severity="secondary" @click="handleDownload" />
                            </div>
                        </div>

                        <!-- History Tab -->
                        <div
                            v-else-if="viewMode === 'history'"
                            class="history-tab-panel animate-fade-in"
                        >
                            <div class="history-tab-header">
                                <div class="flex items-center gap-2">
                                    <i class="pi pi-history text-blue-500"></i>
                                    <h3 class="text-base font-bold text-slate-700 uppercase tracking-tight">Riwayat Arsip</h3>
                                </div>
                                <span v-if="combinedHistory.length" class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">
                                    {{ combinedHistory.length }} item
                                </span>
                            </div>

                            <div class="history-content-container">
                                <div
                                    ref="historyScrollEl"
                                    class="history-scroll-container"
                                    @scroll="handleHistoryScroll"
                                >
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
                                                        <span class="text-[10px] text-slate-500">Dikembalikan pada {{ formatDate(slotProps.item.actual_return_date || slotProps.item.created_at) }}</span>
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

                                    <div v-if="loadingMoreHistory" class="flex items-center justify-center gap-2 py-4 text-xs text-slate-400">
                                        <ProgressSpinner style="width: 18px; height: 18px" strokeWidth="6" />
                                        <span>Memuat riwayat lebih lama...</span>
                                    </div>
                                    <div v-else-if="historyHasMore && combinedHistory.length" class="py-4 text-center text-[11px] font-medium text-slate-400">
                                        Scroll ke bawah untuk memuat riwayat lebih lama.
                                    </div>
                                    <div v-else-if="historyLoaded && combinedHistory.length" class="py-4 text-center text-[11px] font-medium text-slate-300">
                                        Semua riwayat sudah ditampilkan.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Location Tab -->
                        <div v-else class="w-full h-[350px] md:h-full flex flex-col p-2 md:p-4 animate-fade-in">
                            <template v-if="hasPhysicalLocation">
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
                            </template>
                            <div v-else class="flex flex-col items-center justify-center h-full text-slate-300 bg-white border border-slate-200 rounded-lg">
                                <i class="pi pi-map-marker text-5xl mb-4 opacity-20"></i>
                                <p class="italic">Lokasi fisik belum tersedia.</p>
                            </div>
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
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'
import { previewArchive, downloadArchive, requestOtp, verifyOtp, fetchArchiveLocationHistories, logArchiveView } from '@/api/archiveApi'
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

watch(visible, (val) => {
    if (val && props.archive?.id) {
        logArchiveView(props.archive.id).catch(() => {})
    }
})

const viewMode = ref('preview') // 'preview' | 'location' | 'history'
const isUnlocked = ref(false)
const otpSent = ref(false)
const otpCode = ref('')
const loading = ref(false)
const loadingPreview = ref(false)
const previewUrl = ref(null)

const HISTORY_PAGE_SIZE = 8
const historyScrollEl = ref(null)
const locationHistories = ref([])
const checkoutHistories = ref([])
const combinedHistory = ref([])
const loadingHistory = ref(false)
const loadingMoreHistory = ref(false)
const historyLoaded = ref(false)
const historyHasMore = ref(true)
const historyPage = ref(0)
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
    return canViewLocation.value && props.archive?.floor_id && props.archive?.room_id && props.archive?.cabinet_id
})

const canViewLocation = computed(() => {
    return props.archive?.can_view_location !== false
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
    resetHistoryState()
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value)
        previewUrl.value = null
    }
}

const resetHistoryState = () => {
    locationHistories.value = []
    checkoutHistories.value = []
    combinedHistory.value = []
    loadingHistory.value = false
    loadingMoreHistory.value = false
    historyLoaded.value = false
    historyHasMore.value = true
    historyPage.value = 0
}

const activateTab = (mode) => {
    if (mode === 'location' && !canViewLocation.value) {
        viewMode.value = 'preview'
        return
    }

    viewMode.value = mode

    if (mode === 'preview' && !previewUrl.value) {
        loadPreview()
    }

    if (mode === 'history' && !historyLoaded.value) {
        loadHistory({ reset: true })
    }
}

const normalizePaginatedData = (payload) => {
    if (Array.isArray(payload)) {
        return {
            items: payload,
            hasMore: false
        }
    }

    const items = Array.isArray(payload?.data) ? payload.data : []
    const currentPage = Number(payload?.current_page || 1)
    const lastPage = Number(payload?.last_page || currentPage)

    return {
        items,
        hasMore: currentPage < lastPage
    }
}

const getHistoryKey = (item) => `${item.event_type}-${item.id || item.created_at}`

const appendHistoryEvents = (events) => {
    const existingKeys = new Set(combinedHistory.value.map(getHistoryKey))
    const nextEvents = events.filter((item) => !existingKeys.has(getHistoryKey(item)))

    combinedHistory.value = [...combinedHistory.value, ...nextEvents].sort((a, b) =>
        new Date(b.created_at) - new Date(a.created_at)
    )
}

const loadHistory = async ({ reset = false } = {}) => {
    if (!props.archive?.id) return
    if (loadingHistory.value || loadingMoreHistory.value) return
    if (!reset && !historyHasMore.value) return

    if (reset) {
        resetHistoryState()
        loadingHistory.value = true
    } else {
        loadingMoreHistory.value = true
    }

    const nextPage = reset ? 1 : historyPage.value + 1

    try {
        const locationPromise = canViewLocation.value
            ? fetchArchiveLocationHistories(props.archive.id, {
                page: nextPage,
                per_page: HISTORY_PAGE_SIZE
            })
            : Promise.resolve({ data: { data: [] } })

        const [locationRes, checkoutRes] = await Promise.all([
            locationPromise,
            getCheckoutHistory(props.archive.id, {
                page: nextPage,
                per_page: HISTORY_PAGE_SIZE
            })
        ])

        const locationPage = normalizePaginatedData(locationRes.data.data)
        const checkoutPage = normalizePaginatedData(checkoutRes.data.data)

        locationHistories.value = [...locationHistories.value, ...locationPage.items]
        checkoutHistories.value = [...checkoutHistories.value, ...checkoutPage.items]

        const locationEvents = locationPage.items.map(item => ({
            ...item,
            event_type: 'location_move'
        }))

        const checkoutEvents = checkoutPage.items.map(item => ({
            ...item,
            event_type: item.action // 'checkout' or 'checkin'
        }))

        appendHistoryEvents([...locationEvents, ...checkoutEvents])
        historyPage.value = nextPage
        historyHasMore.value = locationPage.hasMore || checkoutPage.hasMore
        historyLoaded.value = true

        if (reset) {
            await nextTick()
            if (historyScrollEl.value) historyScrollEl.value.scrollTop = 0
        }
    } catch (err) {
        console.error('Failed to load history', err)
    } finally {
        loadingHistory.value = false
        loadingMoreHistory.value = false
    }
}

const handleHistoryScroll = (event) => {
    const target = event.target
    if (!target || loadingHistory.value || loadingMoreHistory.value || !historyHasMore.value) return

    const distanceToBottom = target.scrollHeight - target.scrollTop - target.clientHeight
    if (distanceToBottom <= 96) {
        loadHistory()
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
    if (viewMode.value === 'history') {
        loadHistory({ reset: true })
    }
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
        if (viewMode.value === 'history') {
            loadHistory({ reset: true })
        }
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

.archive-detail-dialog :deep(.p-dialog-content),
.archive-detail-dialog :deep(.p-dialog-content > div) {
    min-height: 0;
}

.detail-content-panel {
    align-self: start;
    height: min(680px, calc(100vh - 180px));
    min-height: 500px;
    max-height: calc(100vh - 180px);
}

.tab-content-region {
    flex: 1 1 auto;
    min-height: 0;
    overflow: hidden;
}

.archive-tabbar {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.75rem;
    border-bottom: 1px solid #e2e8f0;
    background:
        linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
}

.archive-tab {
    position: relative;
    display: inline-flex;
    min-height: 2.6rem;
    flex: 1 1 0;
    align-items: center;
    justify-content: center;
    gap: 0.45rem;
    padding: 0.65rem 0.85rem;
    border: 1px solid transparent;
    border-radius: 0.75rem;
    color: #64748b;
    background: transparent;
    font-size: 0.8rem;
    font-weight: 800;
    letter-spacing: 0;
    cursor: pointer;
    transition: color 160ms ease, background 160ms ease, border-color 160ms ease, box-shadow 160ms ease;
}

.archive-tab:hover {
    color: #1d4ed8;
    background: #eff6ff;
    border-color: #bfdbfe;
}

.archive-tab:focus-visible {
    outline: none;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.16);
}

.archive-tab--active {
    color: #1d4ed8;
    border-color: #bfdbfe;
    background: #ffffff;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
}

.archive-tab--active::after {
    position: absolute;
    right: 1rem;
    bottom: -0.78rem;
    left: 1rem;
    height: 2px;
    border-radius: 999px;
    content: "";
    background: linear-gradient(90deg, #2563eb, #0891b2);
}

.archive-tab .pi {
    font-size: 0.9rem;
}

.history-tab-panel {
    display: flex;
    width: 100%;
    height: 100%;
    min-height: 0;
    flex-direction: column;
    overflow: hidden;
    background: #ffffff;
}

.history-tab-header {
    display: flex;
    flex: 0 0 auto;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1.25rem 1.5rem 1rem;
    border-bottom: 1px solid #f1f5f9;
    background: #ffffff;
}

.history-content-container {
    display: flex;
    flex: 1 1 auto;
    min-height: 0;
    margin: 1rem;
    overflow: hidden;
    border: 1px solid #e2e8f0;
    border-radius: 0.9rem;
    background: #f8fafc;
}

.history-scroll-container {
    flex: 1 1 auto;
    min-height: 0;
    height: 100%;
    overflow-y: auto;
    overscroll-behavior: contain;
    padding: 1.25rem 1.5rem 1.5rem;
    scrollbar-gutter: stable;
}

.history-scroll-container::-webkit-scrollbar {
    width: 0.5rem;
}

.history-scroll-container::-webkit-scrollbar-thumb {
    border-radius: 999px;
    background: #cbd5e1;
}

.history-scroll-container::-webkit-scrollbar-track {
    background: #f8fafc;
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

@media (max-width: 640px) {
    .detail-content-panel {
        height: min(620px, calc(100vh - 160px));
        min-height: 460px;
        max-height: calc(100vh - 160px);
    }

    .archive-tabbar {
        gap: 0.25rem;
        padding: 0.5rem;
        overflow-x: auto;
    }

    .archive-tab {
        min-width: max-content;
        flex: 1 0 auto;
        min-height: 2.4rem;
        padding: 0.55rem 0.7rem;
        font-size: 0.74rem;
    }

    .history-tab-header {
        padding: 1rem 1rem 0.85rem;
    }

    .history-content-container {
        margin: 0.75rem;
        border-radius: 0.75rem;
    }

    .history-scroll-container {
        padding: 1rem;
    }
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
