# Tasks

## 1. Kardex Table Responsive Layout

- [x] 1.1 Add `tableContainer` ref and `scrollTable` navigation method to `resources/js/Pages/Admin/Kardex/Index.vue`
- [x] 1.2 Add the horizontal navigation helper bar with `‹` and `›` buttons above the Kardex table
- [x] 1.3 Apply `min-w-[1380px]` on the Kardex `<table>` and `table-scrollbar-visible` class on the scroll wrapper
- [x] 1.4 Add `.table-scrollbar-visible` CSS rules in `<style scoped>` of `Kardex/Index.vue` with 10px height and distinct track/thumb colors
- [x] 1.5 Make the navigation helper bar sticky (`sticky top-0 z-30`) with backdrop blur and ergonomic direction buttons so it stays visible while scrolling vertically

## 2. Verification and Asset Build

- [x] 2.1 Rebuild frontend assets with `npm run build` and verify horizontal scroll behavior on `/admin/kardex`
