import api from '@/services/api'

export function fetchFloors() {
    return api.get('/floors')
}

export function fetchRoomsByFloor(floorId) {
    return api.get('/rooms', { params: { floor_id: floorId } })
}

export function fetchCabinetsByRoom(roomId) {
    return api.get('/cabinets', { params: { room_id: roomId } })
}

export function fetchSlotsByCabinet(cabinetId) {
    return api.get('/cabinet-slots', { params: { cabinet_id: cabinetId } })
}
