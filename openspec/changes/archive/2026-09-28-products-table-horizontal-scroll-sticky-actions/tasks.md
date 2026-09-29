# Tasks

## 1. Global Scrollbar Styling

- [x] 1.1 Update `.custom-scrollbar` utility in `resources/css/app.css` to define `height: 6px` for horizontal scrollbars and ensure visible track/thumb in both light and dark themes

## 2. Products Table Desktop Responsive Layout

- [x] 2.1 Apply `.custom-scrollbar` and ensure wrapper classes in `resources/js/Pages/Admin/Product/Index.vue` allow visible horizontal scrolling
- [x] 2.2 Set `min-w-[1020px]` on the desktop `<table>` element to prevent column crushing and maintain all 8 columns
- [x] 2.3 Implement `sticky right-0` on `th` and `td` of the "Acciones" column with opaque background and elevation shadow for light and dark modes

## 3. Verification and Asset Build

- [x] 3.1 Rebuild frontend assets with `npm run build` and verify horizontal scroll and sticky actions behavior in responsive viewports
