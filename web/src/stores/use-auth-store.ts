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
    isHydrated: boolean
    login: (token: string, user: User) => void
    logout: () => void
    isAuthenticated: () => boolean
    setHydrated: (hydrated: boolean) => void
}

export const useAuthStore = create<AuthState>()(
    persist(
        (set, get) => ({
            user: null,
            token: null,
            isHydrated: false,
            login: (token, user) => set({ token, user }),
            logout: () => set({ token: null, user: null }),
            isAuthenticated: () => !!get().token,
            setHydrated: (hydrated) => set({ isHydrated: hydrated }),
        }),
        {
            name: 'stokio-auth-storage',
            // Exclude isHydrated from persistence - it's runtime state only
            partialize: (state) => ({
                user: state.user,
                token: state.token
            }),
            onRehydrateStorage: () => (state) => {
                // Called when storage is rehydrated (loaded from localStorage)
                state?.setHydrated(true)
            },
        }
    )
)
