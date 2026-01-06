import axios from "axios";
import router from "../router/index.js";

const axiosClient = axios.create({
    baseURL: 'http://localhost:8000/api',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    },
    withCredentials: true
})

// Intercepteur de requête - IMPORTANT : le store sera injecté dynamiquement
axiosClient.interceptors.request.use((config) => {
    // Nous injecterons le store plus tard dans main.js
    const token = localStorage.getItem('token')
    if (token) {
        config.headers.Authorization = `Bearer ${token}`
    }
    return config
})

// Intercepteur de réponse
axiosClient.interceptors.response.use(
    (response) => {
        return response
    },
    async (error) => {
        if (!error.response) {
            console.error('Erreur réseau ou serveur inaccessible')
            return Promise.reject(error)
        }

        const { status, data } = error.response

        // Gestion des erreurs 401 (Non autorisé)
        if (status === 401) {
            // Supprimer le token
            localStorage.removeItem('token')

            // Rediriger vers login
            if (router.currentRoute.value.name !== 'login') {
                router.push({ name: 'login' })
            }

            // Message d'erreur personnalisé
            const message = data?.message || 'Votre session a expiré. Veuillez vous reconnecter.'
            error.message = message
        }

        // Gestion des erreurs 419 (Session expirée - CSRF)
        if (status === 419) {
            try {
                // Rafraîchir le token CSRF
                await axios.get('/sanctum/csrf-cookie', {
                    baseURL: 'http://localhost:8000'
                })

                // Réessayer la requête originale
                return axiosClient(error.config)
            } catch (refreshError) {
                localStorage.removeItem('token')
                router.push({ name: 'login' })
            }
        }

        // Gestion des erreurs 422 (Validation)
        if (status === 422) {
            // Vous pouvez formater les erreurs de validation ici
            const errors = data?.errors || {}
            error.validationErrors = errors
        }

        // Gestion des erreurs 429 (Too Many Requests)
        if (status === 429) {
            const retryAfter = error.response.headers['retry-after'] || 60
            console.warn(`Trop de requêtes. Réessayez dans ${retryAfter} secondes.`)
        }

        // Gestion des erreurs 500 (Serveur)
        if (status >= 500) {
            console.error('Erreur serveur:', data?.message || 'Une erreur est survenue')
        }

        return Promise.reject(error)
    }
)

export default axiosClient
