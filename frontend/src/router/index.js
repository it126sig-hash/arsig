import { createRouter, createWebHistory } from "vue-router";
import AppLayout from "../layouts/AppLayout.vue";

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
      },
      {
        path: "rooms",
        name: "rooms",
        component: () => import("../views/RoomsView.vue"),
      },
      {
        path: "cabinets",
        name: "cabinets",
        component: () => import("../views/CabinetsView.vue"),
      },
      {
        path: "cabinet-slots",
        name: "cabinet-slots",
        component: () => import("../views/CabinetSlotsView.vue"),
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
      },
      {
        path: "departments",
        name: "departments",
        component: () => import("../views/DepartmentView.vue"),
      },
      {
        path: "users",
        name: "users",
        component: () => import("../views/UserView.vue"),
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

// Navigation guard — redirect to login if not authenticated
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

  next();
});

export default router;
