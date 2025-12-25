import type { ColumnDef } from "@tanstack/react-table"
import { ArrowUpDown } from "lucide-react"
import { Button } from "@/components/ui/button"

export type Product = {
    id: string
    name: string
    sku: string
    price: number
    stock: number
    status: "active" | "draft" | "archived"
    image?: string
}

export const columns: ColumnDef<Product>[] = [
    {
        accessorKey: "image",
        header: "Image",
        cell: () => {
            return <div className="h-10 w-10 rounded-md bg-muted border overflow-hidden">
                <div className="w-full h-full bg-slate-200 flex items-center justify-center text-xs text-muted-foreground">IMG</div>
            </div>
        }
    },
    {
        accessorKey: "name",
        header: ({ column }) => {
            return (
                <Button
                    variant="ghost"
                    onClick={() => column.toggleSorting(column.getIsSorted() === "asc")}
                    className="-ml-4"
                >
                    Name
                    <ArrowUpDown className="ml-2 h-4 w-4" />
                </Button>
            )
        },
    },
    {
        accessorKey: "sku",
        header: "SKU",
    },
    {
        accessorKey: "price",
        header: () => <div className="text-right">Price</div>,
        cell: ({ row }) => {
            const amount = parseFloat(row.getValue("price"))
            const formatted = new Intl.NumberFormat("en-US", {
                style: "currency",
                currency: "USD",
            }).format(amount)

            return <div className="text-right font-mono font-medium">{formatted}</div>
        },
    },
    {
        accessorKey: "stock",
        header: "Stock",
        cell: ({ row }) => {
            const stock = parseInt(row.getValue("stock"))
            const isLow = stock < 10
            return <div className={isLow ? "text-amber-500 font-bold" : "text-emerald-500 font-bold"}>{stock}</div>
        }
    },
]
