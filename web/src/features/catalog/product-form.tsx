import { useParams, useNavigate } from "react-router-dom"
import { useForm } from "react-hook-form"
import { z } from "zod"
import { zodResolver } from "@hookform/resolvers/zod"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card"
import { Textarea } from "@/components/ui/textarea"
import { ChevronLeft, Upload, Loader2 } from "lucide-react"
import { useState, useEffect } from "react"
// import api from "@/api/axios"

// Schema
const productSchema = z.object({
    name: z.string().min(2, "Name is required"),
    sku: z.string().min(2, "SKU is required"),
    price: z.coerce.number().min(0.01, "Price must be greater than 0"),
    stock: z.coerce.number().min(0, "Stock cannot be negative"),
    description: z.string().optional(),
})

type ProductFormValues = z.infer<typeof productSchema>
// This creates: { price: number; stock: number; ... }

export default function ProductForm() {
    const { id } = useParams()
    const navigate = useNavigate()
    const isEdit = !!id
    const [loading, setLoading] = useState(false)
    const [imagePreview, setImagePreview] = useState<string | null>(null)

    const { register, handleSubmit, setValue, formState: { errors, isSubmitting } } = useForm<ProductFormValues>({
        resolver: zodResolver(productSchema) as any,
        defaultValues: {
            stock: 0
        }
    })

    useEffect(() => {
        if (isEdit) {
            // Fetch product logic here
            // Mock:
            setValue("name", "Product " + id)
            setValue("sku", "SKU-" + id)
            setValue("price", 99.99)
            setValue("stock", 50)
        }
    }, [isEdit, id, setValue])

    const onSubmit = async (data: ProductFormValues) => {
        setLoading(true)
        try {
            console.log("Saving", data)
            // await api.post('/products', data)
            await new Promise(r => setTimeout(r, 1000))
            navigate('/products')
        } catch (error) {
            console.error(error)
        } finally {
            setLoading(false)
        }
    }

    const handleImageUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0]
        if (file) {
            const reader = new FileReader()
            reader.onloadend = () => {
                setImagePreview(reader.result as string)
            }
            reader.readAsDataURL(file)
        }
    }

    return (
        <div className="space-y-6 max-w-5xl mx-auto pb-10">
            <div className="flex items-center gap-4">
                <Button variant="outline" size="icon" onClick={() => navigate(-1)}>
                    <ChevronLeft className="h-4 w-4" />
                </Button>
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">{isEdit ? 'Edit Product' : 'Create Product'}</h1>
                    <p className="text-sm text-muted-foreground">{isEdit ? 'Manage product details ' : 'Add a new product to your catalog'}</p>
                </div>
                <div className="ml-auto flex items-center gap-2">
                    <Button variant="outline" onClick={() => navigate(-1)}>Cancel</Button>
                    <Button onClick={handleSubmit(onSubmit)} disabled={loading || isSubmitting}>
                        {loading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                        {isEdit ? "Update Product" : "Save Product"}
                    </Button>
                </div>
            </div>

            <div className="grid gap-6 md:grid-cols-2">
                <div className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Product Details</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="name">Name</Label>
                                <Input id="name" placeholder="Wireless Mouse" {...register('name')} />
                                {errors.name && <span className="text-xs text-destructive">{errors.name.message}</span>}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="sku">SKU</Label>
                                <Input id="sku" placeholder="WM-001" {...register('sku')} />
                                {errors.sku && <span className="text-xs text-destructive">{errors.sku.message}</span>}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="description">Description (Optional)</Label>
                                <Textarea id="description" placeholder="Product description..." {...register('description')} />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Pricing & Inventory</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="price">Price</Label>
                                    <Input
                                        id="price"
                                        type="number"
                                        step="0.01"
                                        className="font-mono"
                                        placeholder="0.00"
                                        {...register('price')}
                                    />
                                    {errors.price && <span className="text-xs text-destructive">{errors.price.message}</span>}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="stock">Initial Stock</Label>
                                    <Input
                                        id="stock"
                                        type="number"
                                        className="font-mono"
                                        placeholder="0"
                                        {...register('stock')}
                                    />
                                    {errors.stock && <span className="text-xs text-destructive">{errors.stock.message}</span>}
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Media</CardTitle>
                            <CardDescription>Product image</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="flex flex-col items-center justify-center rounded-md border-2 border-dashed border-muted-foreground/25 p-10 hover:bg-muted/50 transition-colors">
                                {imagePreview ? (
                                    <div className="relative w-full h-64">
                                        <img src={imagePreview} alt="Preview" className="w-full h-full object-contain rounded-md" />
                                        <Button
                                            type="button"
                                            variant="secondary"
                                            size="sm"
                                            className="absolute right-2 top-2"
                                            onClick={() => setImagePreview(null)}
                                        >
                                            Remove
                                        </Button>
                                    </div>
                                ) : (
                                    <div className="text-center">
                                        <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-muted">
                                            <Upload className="h-6 w-6 text-muted-foreground" />
                                        </div>
                                        <h3 className="mt-4 text-lg font-semibold">Upload Image</h3>
                                        <p className="mb-4 text-sm text-muted-foreground">Drag and drop or click to upload</p>
                                        <Label htmlFor="image-upload">
                                            <div className="inline-flex h-9 items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 cursor-pointer">
                                                Select File
                                            </div>
                                            <Input id="image-upload" type="file" className="hidden" accept="image/*" onChange={handleImageUpload} />
                                        </Label>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    )
}
