import { useForm, useFieldArray } from "react-hook-form"
import { z } from "zod"
import { zodResolver } from "@hookform/resolvers/zod"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Card, CardContent, CardHeader, CardTitle, CardFooter } from "@/components/ui/card"
import { ChevronLeft, Trash2, Plus } from "lucide-react"
import { useNavigate } from "react-router-dom"
import { cn } from "@/lib/utils"

const itemSchema = z.object({
    productId: z.string().min(1, "Product is required"),
    quantity: z.coerce.number().min(1),
    unitPrice: z.coerce.number().min(0.01),
})

const poSchema = z.object({
    supplierId: z.string().min(1, "Supplier is required"),
    date: z.string(),
    items: z.array(itemSchema).min(1, "Add at least one item")
})

type PoFormValues = z.infer<typeof poSchema>

export default function PurchaseOrderForm() {
    const navigate = useNavigate()
    const { register, control, handleSubmit, watch, formState: { errors } } = useForm<PoFormValues>({
        resolver: zodResolver(poSchema) as any,
        defaultValues: {
            date: new Date().toISOString().split('T')[0],
            items: [{ productId: "", quantity: 1, unitPrice: 0 }]
        }
    })

    const { fields, append, remove } = useFieldArray({
        control,
        name: "items"
    });

    const items = watch("items")
    const total = items?.reduce((acc, item) => acc + ((item.quantity || 0) * (item.unitPrice || 0)), 0) || 0

    const onSubmit = (data: PoFormValues) => {
        console.log(data)
        // API call
        navigate('/purchasing')
    }

    return (
        <div className="space-y-6 max-w-4xl mx-auto pb-10">
            <div className="flex items-center gap-4">
                <Button variant="outline" size="icon" onClick={() => navigate(-1)}>
                    <ChevronLeft className="h-4 w-4" />
                </Button>
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Create Purchase Order</h1>
                    <p className="text-sm text-muted-foreground">Draft a new order for a supplier.</p>
                </div>
            </div>

            <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Order Details</CardTitle>
                    </CardHeader>
                    <CardContent className="grid md:grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label>Supplier</Label>
                            <select
                                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background disabled:cursor-not-allowed disabled:opacity-50"
                                {...register('supplierId')}
                            >
                                <option value="">Select Supplier</option>
                                <option value="1">Global Tech Distrib</option>
                                <option value="2">Office Supplies Co</option>
                            </select>
                            {errors.supplierId && <span className="text-xs text-destructive">{errors.supplierId.message}</span>}
                        </div>
                        <div className="space-y-2">
                            <Label>Date</Label>
                            <Input type="date" {...register('date')} />
                            {errors.date && <span className="text-xs text-destructive">{errors.date.message}</span>}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between">
                        <CardTitle>Items</CardTitle>
                        <Button type="button" size="sm" variant="outline" onClick={() => append({ productId: "", quantity: 1, unitPrice: 0 })}>
                            <Plus className="mr-2 h-4 w-4" /> Add Item
                        </Button>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {fields.map((field, index) => (
                            <div key={field.id} className="grid grid-cols-12 gap-2 items-end border-b pb-4 last:border-0 last:pb-0">
                                <div className="col-span-12 md:col-span-5 space-y-1">
                                    <Label className={cn(index !== 0 && "hidden md:hidden")}>Product</Label>
                                    <select
                                        className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background disabled:cursor-not-allowed disabled:opacity-50"
                                        {...register(`items.${index}.productId`)}
                                    >
                                        <option value="">Select Product</option>
                                        <option value="1">Wireless Mouse (WM-001)</option>
                                        <option value="2">Keyboard Mechanical (KB-M1)</option>
                                    </select>
                                    {errors.items?.[index]?.productId && <span className="text-xs text-destructive">{errors.items[index]?.productId?.message}</span>}
                                </div>
                                <div className="col-span-4 md:col-span-2 space-y-1">
                                    <Label className={cn(index !== 0 && "hidden md:hidden")}>Quantity</Label>
                                    <Input type="number" {...register(`items.${index}.quantity`)} />
                                </div>
                                <div className="col-span-4 md:col-span-3 space-y-1">
                                    <Label className={cn(index !== 0 && "hidden md:hidden")}>Unit Cost</Label>
                                    <Input type="number" step="0.01" {...register(`items.${index}.unitPrice`)} />
                                </div>
                                <div className="col-span-4 md:col-span-2 flex items-center justify-end gap-2 pb-2">
                                    <Button type="button" variant="ghost" size="icon" className="text-destructive" onClick={() => remove(index)}>
                                        <Trash2 className="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        ))}
                        {errors.items && <span className="text-sm text-destructive block">{errors.items.message}</span>}
                    </CardContent>
                    <CardFooter className="flex justify-end border-t bg-muted/20 p-6">
                        <div className="text-right">
                            <div className="text-sm text-muted-foreground">Total Value</div>
                            <div className="text-2xl font-bold font-mono">
                                {new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(total)}
                            </div>
                        </div>
                    </CardFooter>
                </Card>

                <div className="flex justify-end gap-4">
                    <Button type="button" variant="outline" onClick={() => navigate(-1)}>Cancel</Button>
                    <Button type="submit" size="lg">Create Order</Button>
                </div>
            </form>
        </div>
    )
}
