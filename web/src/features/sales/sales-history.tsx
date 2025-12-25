import { useState } from "react"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Button } from "@/components/ui/button"
import { ChevronDown, ChevronRight, Receipt } from "lucide-react"

type SaleItem = {
    id: string
    productName: string
    quantity: number
    price: number
}

type Sale = {
    id: string
    date: string
    total: number
    paymentMethod: string
    items: SaleItem[]
}

export default function SalesHistory() {
    const [sales] = useState<Sale[]>([
        {
            id: "S-10023",
            date: "2024-12-25 10:30 AM",
            total: 145.99,
            paymentMethod: "Credit Card",
            items: [
                { id: "1", productName: "Wireless Mouse", quantity: 2, price: 25.99 },
                { id: "2", productName: "USB-C Cable", quantity: 1, price: 9.99 },
                { id: "3", productName: "Keyboard Mechanical", quantity: 1, price: 84.02 }
            ]
        },
        {
            id: "S-10022",
            date: "2024-12-25 09:15 AM",
            total: 9.99,
            paymentMethod: "Cash",
            items: [
                { id: "2", productName: "USB-C Cable", quantity: 1, price: 9.99 },
            ]
        },
    ])

    const [expandedId, setExpandedId] = useState<string | null>(null)

    const toggleExpand = (id: string) => {
        setExpandedId(expandedId === id ? null : id)
    }

    return (
        <div className="space-y-6">
            <h2 className="text-2xl font-bold tracking-tight">Sales History</h2>
            <div className="rounded-md border bg-card">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead className="w-[50px]"></TableHead>
                            <TableHead>Receipt #</TableHead>
                            <TableHead>Date</TableHead>
                            <TableHead>Payment</TableHead>
                            <TableHead className="text-right">Total</TableHead>
                            <TableHead className="w-[100px]"></TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {sales.map((sale) => (
                            <>
                                <TableRow key={sale.id} className="cursor-pointer hover:bg-muted/50" onClick={() => toggleExpand(sale.id)}>
                                    <TableCell>
                                        {expandedId === sale.id ? <ChevronDown className="h-4 w-4" /> : <ChevronRight className="h-4 w-4" />}
                                    </TableCell>
                                    <TableCell className="font-mono font-medium">{sale.id}</TableCell>
                                    <TableCell>{sale.date}</TableCell>
                                    <TableCell>{sale.paymentMethod}</TableCell>
                                    <TableCell className="text-right font-bold text-emerald-600">
                                        {new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(sale.total)}
                                    </TableCell>
                                    <TableCell>
                                        <Button variant="ghost" size="icon"><Receipt className="h-4 w-4" /></Button>
                                    </TableCell>
                                </TableRow>
                                {expandedId === sale.id && (
                                    <TableRow className="bg-muted/30 hover:bg-muted/30">
                                        <TableCell colSpan={6} className="p-0">
                                            <div className="p-4 pl-14">
                                                <div className="rounded-md border bg-background p-4 shadow-inner">
                                                    <h4 className="mb-2 text-sm font-semibold">Transaction Details</h4>
                                                    <Table>
                                                        <TableHeader>
                                                            <TableRow className="border-b-0 hover:bg-transparent">
                                                                <TableHead className="h-8">Product</TableHead>
                                                                <TableHead className="h-8 text-right">Qty</TableHead>
                                                                <TableHead className="h-8 text-right">Price</TableHead>
                                                                <TableHead className="h-8 text-right">Total</TableHead>
                                                            </TableRow>
                                                        </TableHeader>
                                                        <TableBody>
                                                            {sale.items.map((item) => (
                                                                <TableRow key={item.id} className="border-b-0 hover:bg-transparent">
                                                                    <TableCell className="py-1">{item.productName}</TableCell>
                                                                    <TableCell className="py-1 text-right">{item.quantity}</TableCell>
                                                                    <TableCell className="py-1 text-right">${item.price}</TableCell>
                                                                    <TableCell className="py-1 text-right font-medium">${(item.quantity * item.price).toFixed(2)}</TableCell>
                                                                </TableRow>
                                                            ))}
                                                        </TableBody>
                                                    </Table>
                                                </div>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                )}
                            </>
                        ))}
                    </TableBody>
                </Table>
            </div>
        </div>
    )
}
