import Vue from "vue";
import Router from "vue-router";

Vue.use(Router);

export default new Router({
    routes: [
        {
            name: "admin",
            path: "/",
            component: () => import("@/views/Admin")
        },

        {
            name: "course",
            path: "/course",
            component: () => import("@/views/Course"),
        }
    ]
});
