import { create } from 'zustand'
import { persist } from 'zustand/middleware'

export interface User {
    id: number
    name: string
    email: string
    role?: string
}

interface AuthState {
    user: User | null
    token: string | null
    login: (token: string, user: User) => void
    logout: () => void
    isAuthenticated: () => boolean
}

export const useAuthStore = create<AuthState>()(
    persist(
        (set, get) => ({
            user: null,
            token: null,
            login: (token, user) => set({ token, user }),
            logout: () => set({ token: null, user: null }),
            isAuthenticated: () => !!get().token,
        }),
        {
            name: 'stokio-auth-storage',
        }
    )
)
