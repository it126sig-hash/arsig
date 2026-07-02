import { createRouter, createWebHistory } from "vue-router";
import AppLayout from "../layouts/AppLayout.vue";
import { useAuthStore } from "../store/auth";

const routes = [
  // Public route — no layout
  {
    path: "/login",
    name: "login",
    component: () => import("../views/LoginView.vue"),
  },

  // Protected routes — wrapped in AppLayout (sidebar + topbar)
  {
    path: "/",
    component: AppLayout,
    children: [
      {
        path: "",
        name: "home",
        component: () => import("../views/HomeView.vue"),
      },
      {
        path: "floors",
        name: "floors",
        component: () => import("../views/FloorsView.vue"),
        meta: { module: "floors" },
      },
      {
        path: "rooms",
        name: "rooms",
        component: () => import("../views/RoomsView.vue"),
        meta: { module: "rooms" },
      },
      {
        path: "cabinets",
        name: "cabinets",
        component: () => import("../views/CabinetsView.vue"),
        meta: { module: "cabinets" },
      },
      {
        path: "cabinet-slots",
        name: "cabinet-slots",
        component: () => import("../views/CabinetSlotsView.vue"),
        meta: { module: "cabinet_slots" },
      },
      {
        path: "file-explorer",
        name: "file-explorer",
        component: () => import("../views/FileExplorerView.vue"),
      },
      {
        path: "companies",
        name: "companies",
        component: () => import("../views/CompanyView.vue"),
        meta: { module: "companies" },
      },
      {
        path: "departments",
        name: "departments",
        component: () => import("../views/DepartmentView.vue"),
        meta: { module: "departments" },
      },
      {
        path: "users",
        name: "users",
        component: () => import("../views/UserView.vue"),
        meta: { module: "users" },
      },
      {
        path: "role-permissions",
        name: "role-permissions",
        component: () => import("../views/RolePermissionView.vue"),
        meta: { adminOnly: true },
      },
      {
        path: "tags",
        name: "tags",
        component: () => import("../views/TagView.vue"),
      },
      {
        path: "approvals",
        name: "approvals",
        component: () => import("../views/RequestApprovalView.vue"),
      },
      {
        path: "location-histories",
        name: "location-histories",
        component: () => import("../views/LocationHistoryView.vue"),
      },
      {
        path: "download-history",
        name: "download-history",
        component: () => import("../views/DownloadHistoryView.vue"),
      },
      {
        path: "profile",
        name: "profile",
        component: () => import("../views/ProfileView.vue"),
      },
    ],
  },
];

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
});

// Navigation guard — redirect to login if not authenticated, block module-gated routes
router.beforeEach((to, from, next) => {
  const publicPages = ["/login"];
  const authRequired = !publicPages.includes(to.path);
  const loggedIn = localStorage.getItem("token");

  if (authRequired && !loggedIn) {
    return next("/login");
  }

  if (to.path === "/login" && loggedIn) {
    return next("/");
  }

  if (loggedIn) {
    const authStore = useAuthStore();
    const role = authStore.user?.role;

    if (to.meta?.adminOnly && role !== "admin" && role !== "root") {
      return next("/");
    }

    if (to.meta?.module && !authStore.canModule(to.meta.module, "view")) {
      return next("/");
    }
  }

  next();
});

export default router;
