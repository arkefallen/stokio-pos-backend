import { useEffect, useState } from "react"
import { columns } from "./components/columns"
import type { Product } from "./components/columns"
import { DataTable } from "./components/data-table"
import { Button } from "@/components/ui/button"
import { Plus } from "lucide-react"
import { useNavigate } from "react-router-dom"
// import api from "@/api/axios"

export default function ProductList() {
    const [data, setData] = useState<Product[]>([])
    const [loading, setLoading] = useState(true)
    const navigate = useNavigate()

    useEffect(() => {
        const fetchData = async () => {
            setLoading(true)
            try {
                // In real scenario:
                // const result = await api.get('/products')
                // setData(result.data.data)

                // Mocking
                setTimeout(() => {
                    setData([
                        { id: "1", name: "Wireless Mouse", sku: "WM-001", price: 25.99, stock: 120, status: "active" },
                        { id: "2", name: "Keyboard Mechanical", sku: "KB-M1", price: 89.50, stock: 5, status: "active" },
                        { id: "3", name: "Monitor 24 inch", sku: "MON-24", price: 159.00, stock: 15, status: "active" },
                        { id: "4", name: "USB-C Cable", sku: "CAB-USBC", price: 9.99, stock: 200, status: "active" },
                        { id: "5", name: "Gaming Headset", sku: "GH-PRO", price: 79.99, stock: 45, status: "active" },
                    ])
                    setLoading(false)
                }, 500)

            } catch (error) {
                console.error("Failed to fetch products", error)
                setLoading(false)
            }
        }
        fetchData()
    }, [])

    return (
        <div className="space-y-4">
            <div className="flex items-center justify-between">
                <div>
                    <h2 className="text-2xl font-bold tracking-tight">Products</h2>
                    <p className="text-sm text-muted-foreground">Manage your product catalog</p>
                </div>
                <Button onClick={() => navigate('/products/create')} className="bg-primary hover:bg-primary/90">
                    <Plus className="mr-2 h-4 w-4" /> Add Product
                </Button>
            </div>

            {/* Loading State or Table */}
            {loading ? (
                <div className="flex items-center justify-center h-64">
                    <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                </div>
            ) : (
                <DataTable columns={columns} data={data} />
            )}
        </div>
    )
}
