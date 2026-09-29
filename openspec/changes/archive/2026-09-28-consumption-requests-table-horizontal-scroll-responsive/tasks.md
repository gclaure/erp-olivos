# Tasks

## 1. Table Layout & Sticky Navigation Bar

- [x] 1.1 Add `tableContainer` ref and `scrollTable(direction)` method in `<script setup>` of `resources/js/Pages/Admin/ConsumptionRequest/Index.vue`
- [x] 1.2 Add the horizontal navigation helper bar (`sticky top-0 z-30`) with backdrop blur and `‹` / `›` buttons above the consumption requests table card
- [x] 1.3 Apply `min-w-[1180px]` on the desktop `<table>` and `table-scrollbar-visible` class on the desktop scroll container
- [x] 1.4 Apply `sticky right-0` to the `Acciones` column headers (`th`) and cells (`td`) with opaque background and elevation shadows
- [x] 1.5 Add `.table-scrollbar-visible` CSS rules in `<style scoped>` of `ConsumptionRequest/Index.vue` with 10px height and light/dark theme contrast

## 2. Verification and Build

- [x] 2.1 Rebuild frontend assets with `npm run build` and verify layout on `/admin/consumption-requests`
