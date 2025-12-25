import { useState } from "react"
import { Button } from "@/components/ui/button"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Plus } from "lucide-react"

export default function SupplierList() {
    const [suppliers] = useState([
        { id: 1, name: "Global Tech Distrib", email: "contact@globaltech.com", phone: "+1 555-0123" },
        { id: 2, name: "Office Supplies Co", email: "sales@officesupplies.co", phone: "+1 555-0124" },
    ])

    return (
        <div className="space-y-4">
            <div className="flex items-center justify-between">
                <div>
                    <h3 className="text-lg font-medium">Supplier Directory</h3>
                    <p className="text-sm text-muted-foreground">Manage your vendor relationships.</p>
                </div>
                <Button>
                    <Plus className="mr-2 h-4 w-4" /> Add Supplier
                </Button>
            </div>
            <div className="rounded-md border bg-card">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Name</TableHead>
                            <TableHead>Email</TableHead>
                            <TableHead>Phone</TableHead>
                            <TableHead className="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {suppliers.map((s) => (
                            <TableRow key={s.id}>
                                <TableCell className="font-medium">{s.name}</TableCell>
                                <TableCell>{s.email}</TableCell>
                                <TableCell>{s.phone}</TableCell>
                                <TableCell className="text-right">
                                    <Button variant="ghost" size="sm">Edit</Button>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>
        </div>
    )
}
