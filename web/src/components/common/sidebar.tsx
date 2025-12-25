import { Link, useLocation } from 'react-router-dom'
import { Home, Package, Truck, ClipboardList, ShoppingCart, LogOut, ChevronLeft, ChevronRight, Users } from 'lucide-react'
import { cn } from '@/lib/utils'
import { useUIStore } from '@/stores/use-ui-store'
import { Button } from '@/components/ui/button'
import { useAuthStore } from '@/stores/use-auth-store'

const navItems = [
    { label: 'Dashboard', href: '/', icon: Home },
    { label: 'Products', href: '/products', icon: Package },
    { label: 'Purchasing', href: '/purchasing', icon: Truck },
    { label: 'Inventory', href: '/inventory', icon: ClipboardList },
    { label: 'Sales', href: '/sales', icon: ShoppingCart },
    // Mock: Role User logic would go here
    { label: 'Users', href: '/users', icon: Users },
]

export function Sidebar() {
    const { sidebarOpen, toggleSidebar } = useUIStore()
    const { pathname } = useLocation()
    const { logout } = useAuthStore()

    return (
        <aside
            className={cn(
                "flex h-screen flex-col border-r bg-card text-card-foreground transition-all duration-300 ease-in-out z-20 sticky top-0",
                sidebarOpen ? "w-64" : "w-16"
            )}
        >
            <div className="flex h-14 items-center justify-between border-b px-3 py-2 bg-background/50 backdrop-blur-sm">
                <div className={cn("flex items-center gap-2 overflow-hidden", !sidebarOpen && "justify-center w-full")}>
                    <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-primary text-primary-foreground font-bold">
                        S
                    </div>
                    {sidebarOpen && <span className="text-lg font-bold">Stokio</span>}
                </div>
                {sidebarOpen && (
                    <Button variant="ghost" size="icon" onClick={toggleSidebar} className="h-8 w-8">
                        <ChevronLeft className="h-4 w-4" />
                    </Button>
                )}
            </div>

            {!sidebarOpen && (
                <div className="flex justify-center py-2 border-b">
                    <Button variant="ghost" size="icon" onClick={toggleSidebar} className="h-8 w-8">
                        <ChevronRight className="h-4 w-4" />
                    </Button>
                </div>
            )}

            <nav className="flex-1 space-y-1 p-2 overflow-y-auto">
                {navItems.map((item) => {
                    const isActive = pathname === item.href || (item.href !== '/' && pathname.startsWith(item.href))
                    return (
                        <Link
                            key={item.href}
                            to={item.href}
                            className={cn(
                                "group flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground relative",
                                isActive ? "bg-accent/80 text-accent-foreground" : "text-muted-foreground",
                                !sidebarOpen && "justify-center px-2"
                            )}
                        >
                            <item.icon className="h-5 w-5 shrink-0" />
                            {sidebarOpen && <span>{item.label}</span>}
                            {!sidebarOpen && (
                                <div className="absolute left-12 hidden rounded-md bg-popover px-2 py-1 text-xs text-popover-foreground shadow-md group-hover:block z-50 whitespace-nowrap border animate-in fade-in slide-in-from-left-2">
                                    {item.label}
                                </div>
                            )}
                        </Link>
                    )
                })}
            </nav>

            <div className="border-t p-2 bg-background/50 backdrop-blur-sm">
                <Button
                    variant="ghost"
                    className={cn("w-full justify-start text-destructive hover:bg-destructive/10 hover:text-destructive", !sidebarOpen && "justify-center")}
                    onClick={logout}
                >
                    <LogOut className="h-5 w-5 shrink-0" />
                    {sidebarOpen && <span className="ml-2">Logout</span>}
                </Button>
            </div>
        </aside>
    )
}
