import { useEffect, useRef, useState } from 'react'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import { zodResolver } from '@hookform/resolvers/zod'
import { gsap } from 'gsap'
import { useAuthStore } from '@/stores/use-auth-store'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardHeader } from '@/components/ui/card'
import { useNavigate } from 'react-router-dom'
import api from '@/api/axios'

const loginSchema = z.object({
    email: z.string().email({ message: "Invalid email address" }),
    password: z.string().min(6, { message: "Password must be at least 6 characters" }),
})

type LoginFormValues = z.infer<typeof loginSchema>

export default function LoginPage() {
    const navigate = useNavigate()
    const { login } = useAuthStore()
    const [loading, setLoading] = useState(false)
    const [error, setError] = useState('')

    const formRef = useRef<HTMLDivElement>(null)
    const brandRef = useRef<HTMLDivElement>(null)

    const { register, handleSubmit, formState: { errors } } = useForm<LoginFormValues>({
        resolver: zodResolver(loginSchema)
    })

    useEffect(() => {
        // Animation
        const tl = gsap.timeline()

        tl.fromTo(brandRef.current,
            { opacity: 0, x: -50 },
            { opacity: 1, x: 0, duration: 1, ease: "power3.out" }
        )

        tl.fromTo(formRef.current,
            { opacity: 0, x: 50 },
            { opacity: 1, x: 0, duration: 1, ease: "power3.out" },
            "-=0.5"
        )

        // Stagger inputs
        const inputs = formRef.current?.querySelectorAll('.animate-input')
        if (inputs && inputs.length > 0) {
            tl.fromTo(inputs,
                { opacity: 0, y: 20 },
                { opacity: 1, y: 0, stagger: 0.1, duration: 0.5, ease: "back.out(1.7)" },
                "-=0.5"
            )
        }
    }, [])

    const onSubmit = async (data: LoginFormValues) => {
        setLoading(true)
        setError('')
        try {
            // Mock Login (Bypassing real API as requested)
            // const response = await api.post('/auth/login', data)
            // const { token, user } = response.data

            await new Promise(r => setTimeout(r, 1000)) // Simulate network delay

            if (data.email === "error@stokio.com") {
                throw new Error("Mock error")
            }

            const mockUser = {
                id: 1,
                name: "Admin User",
                email: data.email,
                role: "admin"
            }

            login("mock-token-xyz-123", mockUser)
            navigate('/')
        } catch (err: any) {
            console.error(err)
            setError('Invalid credentials (mock). Try any email except error@stokio.com')
        } finally {
            setLoading(false)
        }
    }

    return (
        <div className="flex min-h-screen w-full bg-background overflow-hidden">
            {/* Left Brand Section */}
            <div
                ref={brandRef}
                className="hidden w-1/2 flex-col justify-between bg-slate-900 p-12 text-white lg:flex relative"
            >
                <div className="flex items-center gap-2 text-2xl font-bold z-10">
                    <div className="h-8 w-8 rounded bg-primary" />
                    Stokio
                </div>

                {/* Abstract Pattern */}
                <div className="absolute inset-0 opacity-10">
                    <svg className="h-full w-full" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
                                <path d="M0 40L40 0H20L0 20M40 40V20L20 40" stroke="white" strokeWidth="1" fill="none" />
                            </pattern>
                        </defs>
                        <rect width="100%" height="100%" fill="url(#grid)" />
                    </svg>
                </div>

                <div className="space-y-4 z-10 relative">
                    <h1 className="text-4xl font-bold leading-tight tracking-tight">
                        Manage your inventory with <br /> <span className="text-primary">electrical precision.</span>
                    </h1>
                    <p className="text-lg text-slate-400">
                        The modern operating system for high-performance retail.
                    </p>
                </div>
                <div className="flex items-center gap-4 text-sm text-slate-500 z-10">
                    <span>&copy; 2025 Stokio Inc.</span>
                </div>
            </div>

            {/* Right Form Section */}
            <div className="flex flex-1 items-center justify-center p-8 bg-slate-50 dark:bg-slate-900">
                <div ref={formRef} className="w-full max-w-sm space-y-6">
                    <div className="space-y-2 text-center">
                        <h1 className="text-3xl font-bold tracking-tight text-foreground">Welcome back</h1>
                        <p className="text-sm text-muted-foreground">
                            Enter your credentials to access the dashboard
                        </p>
                    </div>

                    <Card className="border-none shadow-xl sm:border bg-card">
                        <CardHeader className="p-0 pb-2" />
                        <CardContent className="space-y-4 p-6 pt-4">
                            <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
                                {error && (
                                    <div className="rounded-md bg-destructive/15 p-3 text-sm text-destructive animate-input">
                                        {error}
                                    </div>
                                )}
                                <div className="space-y-2 animate-input">
                                    <Label htmlFor="email">Email</Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        placeholder="name@example.com"
                                        {...register('email')}
                                        className="bg-background"
                                    />
                                    {errors.email && <span className="text-xs text-destructive">{errors.email.message}</span>}
                                </div>
                                <div className="space-y-2 animate-input">
                                    <Label htmlFor="password">Password</Label>
                                    <Input
                                        id="password"
                                        type="password"
                                        {...register('password')}
                                        className="bg-background"
                                    />
                                    {errors.password && <span className="text-xs text-destructive">{errors.password.message}</span>}
                                </div>
                                <Button type="submit" className="w-full animate-input font-semibold" disabled={loading} size="lg">
                                    {loading ? "Signing in..." : "Sign in"}
                                </Button>
                            </form>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    )
}
