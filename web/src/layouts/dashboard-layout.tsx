import { Outlet, useLocation } from 'react-router-dom'
import { Sidebar } from '@/components/common/sidebar'
import { gsap } from 'gsap'
import { useEffect, useRef } from 'react'

export default function DashboardLayout() {
    const mainRef = useRef<HTMLElement>(null)
    const location = useLocation()

    useEffect(() => {
        // Entrance animation
        gsap.fromTo(mainRef.current,
            { opacity: 0, y: 10 },
            { opacity: 1, y: 0, duration: 0.4, ease: "power2.out" }
        )
    }, [location.pathname]) // Re-run on route change for smooth transition feeling

    // Determine title based on path
    const getTitle = () => {
        if (location.pathname === '/') return 'Overview'
        const parts = location.pathname.split('/').filter(Boolean)
        if (parts.length === 0) return 'Overview'
        return parts[0].charAt(0).toUpperCase() + parts[0].slice(1)
    }

    return (
        <div className="flex min-h-screen bg-background text-foreground font-sans">
            <Sidebar />
            <div className="flex-1 flex flex-col min-h-screen overflow-hidden relative">
                {/* Topbar */}
                <header className="h-14 border-b flex items-center justify-between px-6 bg-background/80 backdrop-blur-md sticky top-0 z-10 transition-all">
                    <div className="flex items-center gap-2">
                        <h1 className="text-lg font-semibold capitalize">{getTitle()}</h1>
                    </div>

                    <div className="flex items-center gap-4">
                        {/* User Dropdown Placeholder */}
                        <div className="h-8 w-8 rounded-full bg-secondary/20 border border-secondary flex items-center justify-center text-xs font-bold text-secondary-foreground cursor-pointer">
                            A
                        </div>
                    </div>
                </header>

                {/* Main Content */}
                <main ref={mainRef} className="flex-1 overflow-y-auto p-6 scroll-smooth">
                    <Outlet />
                </main>
            </div>
        </div>
    )
}
