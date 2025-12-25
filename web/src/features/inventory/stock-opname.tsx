import { useState } from "react"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Input } from "@/components/ui/input"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Loader2, Save, RotateCcw } from "lucide-react"

type StockItem = {
    id: string
    name: string
    sku: string
    systemStock: number
    physicalStock: number | ''
}

export default function StockOpname() {
    const [loading, setLoading] = useState(false)
    const [items, setItems] = useState<StockItem[]>([
        { id: "1", name: "Wireless Mouse", sku: "WM-001", systemStock: 120, physicalStock: '' },
        { id: "2", name: "Keyboard Mechanical", sku: "KB-M1", systemStock: 5, physicalStock: '' },
        { id: "3", name: "Monitor 24 inch", sku: "MON-24", systemStock: 15, physicalStock: '' },
        { id: "4", name: "USB-C Cable", sku: "CAB-USBC", systemStock: 200, physicalStock: '' },
    ])

    const handleCountChange = (id: string, value: string) => {
        const num = value === '' ? '' : parseInt(value)
        setItems(prev => prev.map(item =>
            item.id === id ? { ...item, physicalStock: num as number | '' } : item
        ))
    }

    const calculateVariance = (system: number, physical: number | '') => {
        if (physical === '') return 0
        return physical - system
    }

    const handleSave = () => {
        setLoading(true)
        console.log("Saving Opname", items)
        setTimeout(() => setLoading(false), 1000)
    }

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <div>
                    <h2 className="text-2xl font-bold tracking-tight">Stock Opname</h2>
                    <p className="text-sm text-muted-foreground">Reconcile physical inventory with system records.</p>
                </div>
                <div className="flex items-center gap-2">
                    <Button variant="outline"><RotateCcw className="mr-2 h-4 w-4" /> Reset</Button>
                    <Button onClick={handleSave} disabled={loading}>
                        {loading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                        <Save className="mr-2 h-4 w-4" /> Commit Adjustments
                    </Button>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Inventory List</CardTitle>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>SKU</TableHead>
                                <TableHead>Product Name</TableHead>
                                <TableHead className="text-right">System Stock</TableHead>
                                <TableHead className="w-[150px] text-right">Physical Count</TableHead>
                                <TableHead className="text-right">Variance</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {items.map((item) => {
                                const variance = calculateVariance(item.systemStock, item.physicalStock)
                                const hasVariance = item.physicalStock !== '' && variance !== 0

                                return (
                                    <TableRow key={item.id} className={hasVariance ? (variance > 0 ? "bg-emerald-50/50 dark:bg-emerald-900/10" : "bg-red-50/50 dark:bg-red-900/10") : ""}>
                                        <TableCell className="font-mono">{item.sku}</TableCell>
                                        <TableCell>{item.name}</TableCell>
                                        <TableCell className="text-right font-mono">{item.systemStock}</TableCell>
                                        <TableCell className="text-right">
                                            <Input
                                                type="number"
                                                className="text-right font-mono h-8"
                                                value={item.physicalStock}
                                                onChange={(e) => handleCountChange(item.id, e.target.value)}
                                                placeholder="-"
                                            />
                                        </TableCell>
                                        <TableCell className="text-right font-mono font-bold">
                                            {item.physicalStock !== '' ? (
                                                <span className={variance > 0 ? "text-emerald-600" : variance < 0 ? "text-destructive" : "text-muted-foreground"}>
                                                    {variance > 0 ? "+" : ""}{variance}
                                                </span>
                                            ) : "-"}
                                        </TableCell>
                                    </TableRow>
                                )
                            })}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    )
}
