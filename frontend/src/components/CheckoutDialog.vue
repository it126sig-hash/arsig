<template>
    <Dialog 
        v-model:visible="visible" 
        header="Keluarkan Arsip" 
        modal 
        class="p-fluid max-w-2xl w-full"
    >
        <div class="flex flex-col gap-4 mt-2">
            <!-- Archive Info -->
            <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                <div class="text-[10px] uppercase font-bold text-slate-400 mb-2 tracking-wider">Arsip yang dipilih</div>
                <div class="font-bold text-slate-800 text-lg leading-tight mb-1">{{ archive?.name }}</div>
                <div class="text-xs text-slate-500">{{ archive?.file_number }}</div>
            </div>

            <div class="flex flex-col gap-4">
                <!-- Borrower Name -->
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-bold text-slate-600">Nama Peminjam <span class="text-red-500">*</span></label>
                    <InputText 
                        v-model="form.borrower_name" 
                        placeholder="Masukkan nama peminjam"
                    />
                </div>

                <!-- Reason -->
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-bold text-slate-600">Alasan Keluar <span class="text-red-500">*</span></label>
                    <Textarea 
                        v-model="form.reason" 
                        rows="3" 
                        placeholder="Contoh: Untuk keperluan audit tahunan..."
                    />
                </div>

                <!-- Planned Return Date -->
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-bold text-slate-600">Rencana Dikembalikan <span class="text-red-500">*</span></label>
                    <DatePicker 
                        v-model="form.planned_return_date" 
                        dateFormat="dd/mm/yy"
                        :minDate="minDate"
                        showIcon
                        placeholder="Pilih tanggal"
                    />
                </div>

                <!-- Notes -->
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-bold text-slate-600">Catatan Tambahan</label>
                    <Textarea 
                        v-model="form.notes" 
                        rows="2" 
                        placeholder="Catatan opsional..."
                    />
                </div>
            </div>
        </div>

        <template #footer>
            <Button label="Batal" icon="pi pi-times" severity="secondary" text @click="visible = false" />
            <Button 
                label="Simpan & Keluarkan" 
                icon="pi pi-external-link" 
                severity="primary" 
                :loading="submitting"
                :disabled="!isFormValid"
                @click="handleSubmit"
            />
        </template>
    </Dialog>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import DatePicker from 'primevue/datepicker'
import { useToast } from 'primevue/usetoast'
import { checkoutArchive } from '@/api/archiveCheckoutApi'

const props = defineProps({
    modelValue: Boolean,
    archive: Object
})

const emit = defineEmits(['update:modelValue', 'checked-out'])

const toast = useToast()
const visible = computed({
    get: () => props.modelValue,
    set: (val) => emit('update:modelValue', val)
})

const form = reactive({
    borrower_name: '',
    reason: '',
    planned_return_date: null,
    notes: ''
})

const submitting = ref(false)

const minDate = computed(() => {
    const today = new Date()
    today.setHours(0, 0, 0, 0)
    return today
})

const isFormValid = computed(() => {
    return form.borrower_name && form.reason && form.planned_return_date
})

watch(() => props.modelValue, (newVal) => {
    if (newVal) {
        resetForm()
    }
})

const resetForm = () => {
    form.borrower_name = ''
    form.reason = ''
    form.planned_return_date = null
    form.notes = ''
}

const handleSubmit = async () => {
    submitting.value = true
    try {
        const payload = {
            borrower_name: form.borrower_name,
            reason: form.reason,
            planned_return_date: formatDateForApi(form.planned_return_date),
            notes: form.notes || null
        }
        const res = await checkoutArchive(props.archive.id, payload)
        toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Arsip berhasil dikeluarkan', life: 3000 })
        emit('checked-out', res.data.data)
        visible.value = false
    } catch (err) {
        toast.add({ severity: 'error', summary: 'Gagal', detail: err.response?.data?.message || 'Gagal mengeluarkan arsip', life: 3000 })
    } finally {
        submitting.value = false
    }
}

const formatDateForApi = (date) => {
    if (!date) return null
    const d = new Date(date)
    const year = d.getFullYear()
    const month = String(d.getMonth() + 1).padStart(2, '0')
    const day = String(d.getDate()).padStart(2, '0')
    return `${year}-${month}-${day}`
}
</script>
