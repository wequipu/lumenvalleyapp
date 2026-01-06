// stores/auth.js
import { defineStore } from 'pinia'
import axios from 'axios'

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        token: localStorage.getItem('token') || null,
    }),

    getters: {
        isAuthenticated: (state) => !!state.token,
    },

    actions: {
        async login(credentials) {
            try {
                const response = await axios.post(
                    'http://localhost:8000/api/login',
                    credentials
                )

                this.token = response.data.token
                this.user = response.data.user

                localStorage.setItem('token', this.token)

                // Configurer l'en-tête d'autorisation pour les requêtes futures
                axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`

                return response.data
            } catch (error) {
                throw error.response?.data || error
            }
        },

        async register(userData) {
            try {
                const response = await axios.post(
                    'http://localhost:8000/api/register',
                    userData
                )

                this.token = response.data.token
                this.user = response.data.user

                localStorage.setItem('token', this.token)
                axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`

                return response.data
            } catch (error) {
                throw error.response?.data || error
            }
        },

        async logout() {
            try {
                await axios.post('http://localhost:8000/api/logout')
            } finally {
                this.clearAuth()
            }
        },

        async fetchUser() {
            if (!this.token) return

            try {
                axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`
                const response = await axios.get('http://localhost:8000/api/user')
                this.user = response.data
            } catch (error) {
                this.clearAuth()
            }
        },

        clearAuth() {
            this.user = null
            this.token = null
            localStorage.removeItem('token')
            delete axios.defaults.headers.common['Authorization']
        },

        initialize() {
            if (this.token) {
                axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`
                this.fetchUser()
            }
        }
    }
})
