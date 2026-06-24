# Graph Report - frontend  (2026-05-29)

## Corpus Check
- 109 files · ~50,159 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 615 nodes · 1023 edges · 64 communities (55 shown, 9 thin omitted)
- Extraction: 95% EXTRACTED · 5% INFERRED · 0% AMBIGUOUS · INFERRED: 50 edges (avg confidence: 0.8)
- Token cost: 0 input · 0 output

## Community Hubs (Navigation)
- [[_COMMUNITY_Archive API Layer|Archive API Layer]]
- [[_COMMUNITY_Admin CRUD APIs|Admin CRUD APIs]]
- [[_COMMUNITY_Bundled Runtime (Vue)|Bundled Runtime (Vue)]]
- [[_COMMUNITY_Cabinet Polygon Drawing|Cabinet Polygon Drawing]]
- [[_COMMUNITY_Bundled Runtime (Reactivity)|Bundled Runtime (Reactivity)]]
- [[_COMMUNITY_Location Management|Location Management]]
- [[_COMMUNITY_Package Dependencies|Package Dependencies]]
- [[_COMMUNITY_App Navigation & Topbar|App Navigation & Topbar]]
- [[_COMMUNITY_Room Polygon Drawing|Room Polygon Drawing]]
- [[_COMMUNITY_Floor Plan Viewer|Floor Plan Viewer]]
- [[_COMMUNITY_Bundled Runtime (DOM)|Bundled Runtime (DOM)]]
- [[_COMMUNITY_Location Store & Views|Location Store & Views]]
- [[_COMMUNITY_Bundled Runtime (Scheduler)|Bundled Runtime (Scheduler)]]
- [[_COMMUNITY_Archive Checkout Flow|Archive Checkout Flow]]
- [[_COMMUNITY_Bundled Runtime (Core)|Bundled Runtime (Core)]]
- [[_COMMUNITY_Community 15|Community 15]]
- [[_COMMUNITY_Community 16|Community 16]]
- [[_COMMUNITY_Community 17|Community 17]]
- [[_COMMUNITY_Community 18|Community 18]]
- [[_COMMUNITY_Community 19|Community 19]]
- [[_COMMUNITY_Community 20|Community 20]]
- [[_COMMUNITY_Community 21|Community 21]]
- [[_COMMUNITY_Community 22|Community 22]]
- [[_COMMUNITY_Community 23|Community 23]]
- [[_COMMUNITY_Community 24|Community 24]]
- [[_COMMUNITY_Community 25|Community 25]]
- [[_COMMUNITY_Community 26|Community 26]]
- [[_COMMUNITY_Community 28|Community 28]]
- [[_COMMUNITY_Community 29|Community 29]]
- [[_COMMUNITY_Community 30|Community 30]]
- [[_COMMUNITY_Community 31|Community 31]]
- [[_COMMUNITY_Community 32|Community 32]]
- [[_COMMUNITY_Community 33|Community 33]]
- [[_COMMUNITY_Community 38|Community 38]]
- [[_COMMUNITY_Community 39|Community 39]]
- [[_COMMUNITY_Community 40|Community 40]]
- [[_COMMUNITY_Community 41|Community 41]]

## God Nodes (most connected - your core abstractions)
1. `d` - 31 edges
2. `H()` - 24 edges
3. `push()` - 19 edges
4. `V()` - 19 edges
5. `fn()` - 18 edges
6. `ki()` - 18 edges
7. `t()` - 17 edges
8. `R()` - 17 edges
9. `set()` - 17 edges
10. `cr()` - 17 edges

## Surprising Connections (you probably didn't know these)
- `ye()` --calls--> `d`  [INFERRED]
  android/app/src/main/assets/public/assets/index-Cvd4nogV.js → src/components/CheckoutDialog.vue
- `Le()` --calls--> `d`  [INFERRED]
  android/app/src/main/assets/public/assets/index-Cvd4nogV.js → src/components/CheckoutDialog.vue
- `I()` --calls--> `d`  [INFERRED]
  android/app/src/main/assets/public/assets/index-Cvd4nogV.js → src/components/CheckoutDialog.vue
- `R()` --calls--> `d`  [INFERRED]
  android/app/src/main/assets/public/assets/index-Cvd4nogV.js → src/components/CheckoutDialog.vue
- `get()` --calls--> `d`  [INFERRED]
  android/app/src/main/assets/public/assets/index-Cvd4nogV.js → src/components/CheckoutDialog.vue

## Hyperedges (group relationships)
- **Archive Access & Management Workflow** — archive_management, otp_access_control, checkout_tracking, physical_location_system [INFERRED 0.80]

## Communities (64 total, 9 thin omitted)

### Community 0 - "Archive API Layer"
Cohesion: 0.06
Nodes (25): fetchArchives(), requestOtp(), verifyOtp(), fetchCategoryTree(), fetchCompanies(), filterByBorrowed(), filterByExpiring(), filterByInCabinet() (+17 more)

### Community 1 - "Admin CRUD APIs"
Cohesion: 0.04
Nodes (28): fetchDepartments(), fetchTags(), fetchUsers(), allUsers, availableTags, cabinets, departments, emit (+20 more)

### Community 2 - "Bundled Runtime (Vue)"
Cohesion: 0.07
Nodes (23): Da(), deleteProperty(), ea(), every(), filter(), find(), findIndex(), findLast() (+15 more)

### Community 3 - "Cabinet Polygon Drawing"
Cohesion: 0.06
Nodes (21): didPan, flatPoints, getMinPos(), handleMouseMove(), handleStageClick(), imageConfig, imgDisplayHeight, imgDisplayWidth (+13 more)

### Community 4 - "Bundled Runtime (Reactivity)"
Cohesion: 0.14
Nodes (33): At(), cr(), defineProperty(), Dt(), Et(), F(), fn(), forEach() (+25 more)

### Community 5 - "Location Management"
Cohesion: 0.07
Nodes (28): fetchCabinetsByRoom(), fetchFloors(), fetchRoomsByFloor(), fetchSlotsByCabinet(), cabinets, emit, floors, form (+20 more)

### Community 6 - "Package Dependencies"
Cohesion: 0.06
Nodes (32): dependencies, axios, @capacitor/android, @capacitor/camera, @capacitor/cli, @capacitor-community/file-opener, @capacitor/core, @capacitor/filesystem (+24 more)

### Community 7 - "App Navigation & Topbar"
Cohesion: 0.06
Nodes (21): auth, breadcrumbs, route, routeLabelMap, router, showUserMenu, userEmail, userInitial (+13 more)

### Community 8 - "Room Polygon Drawing"
Cohesion: 0.07
Nodes (19): didPan, flatPoints, handleMouseMove(), handleStageClick(), imageConfig, imgDisplayHeight, imgDisplayWidth, isDraggingPoint (+11 more)

### Community 9 - "Floor Plan Viewer"
Cohesion: 0.07
Nodes (22): containerRef, flatCabinetPoints, flatRoomPoints, floorImage, imageConfig, imageLoaded, imgDisplayHeight, imgDisplayWidth (+14 more)

### Community 10 - "Bundled Runtime (DOM)"
Cohesion: 0.13
Nodes (27): bn(), ci(), cn(), hn(), ln(), Mn(), n(), P() (+19 more)

### Community 11 - "Location Store & Views"
Cohesion: 0.10
Nodes (14): useLocationStore, appBase, confirm, filteredFloors, filters, floor, floorDialog, globalFilter (+6 more)

### Community 12 - "Bundled Runtime (Scheduler)"
Cohesion: 0.17
Nodes (19): a, ai(), br(), dn(), dr(), en(), fi(), includes() (+11 more)

### Community 13 - "Archive Checkout Flow"
Cohesion: 0.12
Nodes (14): checkinArchive(), checkoutArchive(), day, emit, form, handleSubmit(), isFormValid, minDate (+6 more)

### Community 14 - "Bundled Runtime (Core)"
Cohesion: 0.26
Nodes (16): B(), constructor(), e(), er(), gr(), gt(), H(), hr() (+8 more)

### Community 15 - "Community 15"
Cohesion: 0.17
Nodes (6): doorCountTotal, floor, isDoorCountValid, room, roomId, selectedFloorRooms

### Community 16 - "Community 16"
Cohesion: 0.20
Nodes (11): Ga(), Ge(), Mi(), ni(), oi(), Pi(), ti(), track() (+3 more)

### Community 17 - "Community 17"
Cohesion: 0.27
Nodes (10): dirty(), Fe(), Ie(), Le(), Pe(), Re(), run(), runIfDirty() (+2 more)

### Community 18 - "Community 18"
Cohesion: 0.27
Nodes (10): Ar(), concat(), ji(), jn(), map(), Ra(), toReversed(), toSorted() (+2 more)

### Community 19 - "Community 19"
Cohesion: 0.28
Nodes (9): L(), lastIndexOf(), Mt(), nt(), reduce(), reduceRight(), Te(), tt() (+1 more)

### Community 20 - "Community 20"
Cohesion: 0.22
Nodes (6): authStore, confirm, passForm, router, showPasswordDialog, toast

### Community 21 - "Community 21"
Cohesion: 0.25
Nodes (8): ARSIG Frontend, Capacitor 6 Mobile, FCM Push Notifications, Konva.js Canvas Library, Physical Location System, Pinia State Management, PrimeVue UI Framework, Vue 3 Framework

### Community 22 - "Community 22"
Cohesion: 0.33
Nodes (3): saveRoom(), selectedFloorImageUrl, validatePoints()

### Community 23 - "Community 23"
Cohesion: 0.40
Nodes (5): ba(), Ca(), indexOf(), va(), wa()

### Community 24 - "Community 24"
Cohesion: 0.50
Nodes (3): appId, appName, webDir

### Community 25 - "Community 25"
Cohesion: 0.50
Nodes (3): appId, appName, webDir

### Community 26 - "Community 26"
Cohesion: 0.50
Nodes (4): App Layout System, Axios HTTP Client, JWT Authentication Flow, Navigation Guard

### Community 31 - "Community 31"
Cohesion: 0.67
Nodes (3): Archive Management, Checkout/Borrowing Tracking, OTP Access Control

## Knowledge Gaps
- **202 isolated node(s):** `appId`, `appName`, `webDir`, `name`, `private` (+197 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **9 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `d` connect `Bundled Runtime (Scheduler)` to `Bundled Runtime (Vue)`, `Bundled Runtime (Reactivity)`, `Archive Checkout Flow`, `Bundled Runtime (Core)`, `Community 16`, `Community 17`, `Community 18`, `Community 19`, `Community 23`?**
  _High betweenness centrality (0.136) - this node is a cross-community bridge._
- **Are the 30 inferred relationships involving `d` (e.g. with `ue()` and `k()`) actually correct?**
  _`d` has 30 INFERRED edges - model-reasoned connections that need verification._
- **What connects `appId`, `appName`, `webDir` to the rest of the system?**
  _206 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Archive API Layer` be split into smaller, more focused modules?**
  _Cohesion score 0.060129509713228495 - nodes in this community are weakly interconnected._
- **Should `Admin CRUD APIs` be split into smaller, more focused modules?**
  _Cohesion score 0.044444444444444446 - nodes in this community are weakly interconnected._
- **Should `Bundled Runtime (Vue)` be split into smaller, more focused modules?**
  _Cohesion score 0.07422402159244265 - nodes in this community are weakly interconnected._
- **Should `Cabinet Polygon Drawing` be split into smaller, more focused modules?**
  _Cohesion score 0.06417112299465241 - nodes in this community are weakly interconnected._