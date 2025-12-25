import { useState } from "react"
import { Button } from "@/components/ui/button"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Plus } from "lucide-react"
import { useNavigate } from "react-router-dom"

export default function PurchaseOrderList() {
    const navigate = useNavigate()
    const [pos] = useState([
        { id: "PO-001", supplier: "Global Tech Distrib", date: "2024-12-20", total: "$1,200.00", status: "received" },
        { id: "PO-002", supplier: "Office Supplies Co", date: "2024-12-24", total: "$450.50", status: "draft" },
    ])

    return (
        <div className="space-y-4">
            <div className="flex items-center justify-between">
                <div>
                    <h3 className="text-lg font-medium">Active Orders</h3>
                    <p className="text-sm text-muted-foreground">Track purchase orders and stock receipts.</p>
                </div>
                <Button onClick={() => navigate('/purchase-orders/create')}>
                    <Plus className="mr-2 h-4 w-4" /> Create PO
                </Button>
            </div>
            <div className="rounded-md border bg-card">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>PO #</TableHead>
                            <TableHead>Supplier</TableHead>
                            <TableHead>Date</TableHead>
                            <TableHead>Total</TableHead>
                            <TableHead>Status</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {pos.map((po) => (
                            <TableRow key={po.id}>
                                <TableCell className="font-mono font-medium">{po.id}</TableCell>
                                <TableCell>{po.supplier}</TableCell>
                                <TableCell>{po.date}</TableCell>
                                <TableCell className="font-mono">{po.total}</TableCell>
                                <TableCell>
                                    <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${po.status === 'received' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-400'
                                        }`}>
                                        {po.status.charAt(0).toUpperCase() + po.status.slice(1)}
                                    </span>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>
        </div>
    )
}
