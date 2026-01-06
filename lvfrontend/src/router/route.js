const routes = [
    {
        path: "/",
        name: "login",
        component: () => import("./../views/auth/login.vue"),
        meta: {
            title: "Connexion"
        }
    },
    {
        path: "/register",
        name: "register",
        component: () => import("./../views/auth/register.vue"),
        meta: {
            title: "Inscription"
        }
    }
];

export default routes;
