import Vue from "vue";
import { createRouter, createWebHashHistory } from "vue-router";

export default createRouter({
    history: createWebHashHistory(),
    routes: [
        {
            name: "admin",
            path: "/admin",
            component: () => import("@/views/Admin")
        },
        {
            name: "lobby",
            path: "/lobby",
            component: () => import("@/views/Lobby")
        },
        {
            name: "course",
            path: "/",
            component: () => import("@/views/Course"),
        }
    ]
});
