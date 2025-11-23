<style>
/* Page-scoped fix: ensure Create User dropdown overlays above .surface.p-4 */
.admin-users-page .dropdown,
.admin-users-page .btn-group {
    position: relative; /* create a positioning context for absolute dropdown */
}

.admin-users-page .dropdown-menu {
    position: absolute !important;
    z-index: 9999 !important; /* high z-index so it sits above .surface.p-4 */
    top: 100% !important;
    left: 0;
    /* optional: ensure dropdown doesn't get clipped if any ancestor has overflow hidden */
    overflow: visible;
    -webkit-overflow-scrolling: auto;
}
</style>